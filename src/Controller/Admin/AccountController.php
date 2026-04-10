<?php

namespace App\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\File;

final class AccountController extends AbstractController
{
    private string $profilePictureDirectory;
    private SluggerInterface $slugger;

    public function __construct(string $profilePictureDirectory, SluggerInterface $slugger)
    {
        $this->profilePictureDirectory = $profilePictureDirectory;
        $this->slugger = $slugger;
    }

    #[Route('/profile', name: 'app_admin_account_profile')]
    public function profile(): Response
    {
        // Allow both admins and staff to view their profile
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_STAFF')) {
            throw $this->createAccessDeniedException('Access Denied. Admin or Staff role required.');
        }

        $user = $this->getUser();

        return $this->render('admin/account/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/change-password', name: 'app_admin_account_change_password', methods: ['GET', 'POST'])]
    public function changePassword(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $hasher): Response
    {
        // Allow both admins and staff to change their password
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_STAFF')) {
            throw $this->createAccessDeniedException('Access Denied. Admin or Staff role required.');
        }

        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $currentPassword = $request->request->get('current_password');
            $newPassword = $request->request->get('new_password');
            $confirmPassword = $request->request->get('confirm_password');

            // Verify current password
            if (!$hasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Current password is incorrect');
                return $this->redirectToRoute('app_admin_account_change_password');
            }

            // Verify new passwords match
            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'New passwords do not match');
                return $this->redirectToRoute('app_admin_account_change_password');
            }

            // Validate password strength
            if (strlen($newPassword) < 6) {
                $this->addFlash('error', 'Password must be at least 6 characters long');
                return $this->redirectToRoute('app_admin_account_change_password');
            }

            $user->setPassword($hasher->hashPassword($user, $newPassword));
            $entityManager->flush();

            $this->addFlash('success', 'Password changed successfully');
            return $this->redirectToRoute('app_admin_account_profile');
        }

        return $this->render('admin/account/change-password.html.twig');
    }

    #[Route('/update-profile', name: 'app_admin_account_update_profile', methods: ['GET', 'POST'])]
    public function updateProfile(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Allow both admins and staff to update their profile
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_STAFF')) {
            throw $this->createAccessDeniedException('Access Denied. Admin or Staff role required.');
        }

        $user = $this->getUser();

        $firstName = trim($request->request->get('first_name', ''));
        $lastName = trim($request->request->get('last_name', ''));
        $email = trim($request->request->get('email', ''));

        // Basic validation
        if (empty($firstName) || empty($lastName)) {
            $this->addFlash('error', 'First name and last name are required');
            return $this->redirectToRoute('app_admin_account_edit_profile_details');
        }

        if (strlen($firstName) > 50 || strlen($lastName) > 50) {
            $this->addFlash('error', 'First name and last name must be 50 characters or less');
            return $this->redirectToRoute('app_admin_account_edit_profile_details');
        }

        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setEmail($email);
        $entityManager->flush();

        $this->addFlash('success', 'Profile updated successfully');
        return $this->redirectToRoute('app_admin_account_view_profile_details');
    }

    #[Route('/upload-profile-picture', name: 'app_admin_account_upload_profile_picture', methods: ['POST'])]
    public function uploadProfilePicture(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_STAFF')) {
            throw $this->createAccessDeniedException('Access Denied.');
        }

        $user = $this->getUser();
        $uploadedFile = $request->files->get('profile_picture');

        if (!$uploadedFile) {
            $this->addFlash('error', 'No file uploaded');
            return $this->redirectToRoute('app_admin_account_edit_profile_details');
        }

        // Validate file
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($uploadedFile->getMimeType(), $allowedMimes)) {
            $this->addFlash('error', 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed');
            return $this->redirectToRoute('app_admin_account_edit_profile_details');
        }

        if ($uploadedFile->getSize() > 2 * 1024 * 1024) { // 2MB
            $this->addFlash('error', 'File size exceeds 2MB limit');
            return $this->redirectToRoute('app_admin_account_edit_profile_details');
        }

        try {
            // Delete old picture if exists
            if ($user->getProfilePicture()) {
                $oldFilePath = $this->profilePictureDirectory . '/' . $user->getProfilePicture();
                if (file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                }
            }

            // Generate new filename
            $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $uploadedFile->guessExtension();

            // Move file
            $uploadedFile->move($this->profilePictureDirectory, $newFilename);

            // Update user
            $user->setProfilePicture($newFilename);
            $entityManager->flush();

            $this->addFlash('success', 'Profile picture uploaded successfully');
        } catch (\Exception $e) {
            $this->addFlash('error', 'An error occurred while uploading the file');
        }

        return $this->redirectToRoute('app_admin_account_edit_profile_details');
    }

    #[Route('/remove-profile-picture', name: 'app_admin_account_remove_profile_picture', methods: ['POST'])]
    public function removeProfilePicture(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_STAFF')) {
            throw $this->createAccessDeniedException('Access Denied.');
        }

        $user = $this->getUser();

        if ($user->getProfilePicture()) {
            $filePath = $this->profilePictureDirectory . '/' . $user->getProfilePicture();
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $user->setProfilePicture(null);
            $entityManager->flush();
        }

        return new JsonResponse(['success' => true]);
    }

    #[Route('/edit_profile_details', name: 'app_admin_account_edit_profile_details', methods: ['GET'])]
    public function editProfileDetails(): Response
    {
        // Allow both admins and staff to view their profile details
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_STAFF')) {
            throw $this->createAccessDeniedException('Access Denied. Admin or Staff role required.');
        }

        $user = $this->getUser();

        return $this->render('admin/account/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/manage_data', name: 'app_admin_account_manage_data', methods: ['GET'])]
    public function manageData(): Response
    {
        // Restrict data management to admins only
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Access Denied. Admin role required for data management.');
        }

        $user = $this->getUser();

        return $this->render('admin/account/index.html.twig', [
            'user' => $user,
        ]);

    }

    #[Route('/create-backup', name: 'app_admin_account_create_backup', methods: ['POST'])]
    public function createBackup(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_STAFF')) {
            throw $this->createAccessDeniedException('Access Denied.');
        }

        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
        $format = $data['format'] ?? 'json';

        $backupData = [
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'created_at' => $user->getCreatedAt()?->format('Y-m-d H:i:s'),
            ],
        ];

        // You can add more data here (orders, addresses, etc.)

        return new JsonResponse(['success' => true, 'message' => 'Backup created successfully']);
    }

    #[Route('/export_data', name: 'app_admin_account_export_data', methods: ['POST'])]
    public function exportData(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_STAFF')) {
            throw $this->createAccessDeniedException('Access Denied.');
        }

        $user = $this->getUser();
        $format = $request->request->get('format', 'json');

        $exportData = [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'created_at' => $user->getCreatedAt()?->format('Y-m-d H:i:s'),
            'exported_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ];

        $filename = 'user-data-' . date('Y-m-d-H-i-s');

        if ($format === 'csv') {
            return $this->exportAsCSV($exportData, $filename);
        } elseif ($format === 'sql') {
            return $this->exportAsSQL($exportData, $filename, $user);
        } elseif ($format === 'excel') {
            return $this->exportAsExcel($exportData, $filename);
        } else {
            return $this->exportAsJSON($exportData, $filename);
        }
    }

    #[Route('/import_data', name: 'app_admin_account_import_data', methods: ['POST'])]
    public function importData(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_STAFF')) {
            throw $this->createAccessDeniedException('Access Denied.');
        }

        $uploadedFile = $request->files->get('import_file');

        if (!$uploadedFile) {
            $this->addFlash('error', 'No file uploaded');
            return $this->redirectToRoute('app_admin_account_manage_data');
        }

        $user = $this->getUser();
        $mergeData = $request->request->get('merge_data') === 'on';
        $skipDuplicates = $request->request->get('skip_duplicates') === 'on';

        try {
            $fileContent = file_get_contents($uploadedFile->getPathname());
            $extension = $uploadedFile->guessExtension();

            if ($extension === 'json') {
                $data = json_decode($fileContent, true);
                if ($data && isset($data['email'])) {
                    if (!$mergeData || $data['email'] === $user->getEmail()) {
                        $user->setEmail($data['email'] ?? $user->getEmail());
                        $user->setFirstName($data['first_name'] ?? $user->getFirstName());
                        $user->setLastName($data['last_name'] ?? $user->getLastName());
                    }
                }
            } elseif ($extension === 'csv') {
                // CSV parsing logic
                $lines = explode("\n", $fileContent);
                if (count($lines) > 1) {
                    $data = str_getcsv($lines[1]);
                    // Process CSV data
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Data imported successfully');
        } catch (\Exception $e) {
            $this->addFlash('error', 'An error occurred while importing the file: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_account_manage_data');
    }

    #[Route('/delete_data', name: 'app_admin_account_delete_data', methods: ['POST'])]
    public function deleteData(EntityManagerInterface $entityManager): Response
    {
        // Allow both admins and staff to delete their data
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_STAFF')) {
            throw $this->createAccessDeniedException('Access Denied. Admin or Staff role required.');
        }

        $user = $this->getUser();

        // Delete profile picture if exists
        if ($user->getProfilePicture()) {
            $filePath = $this->profilePictureDirectory . '/' . $user->getProfilePicture();
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        // Delete user and related data
        $entityManager->remove($user);
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/view_profile_details', name: 'app_admin_account_view_profile_details', methods: ['GET'])]
    public function viewProfileDetails(): Response
    {
        // Allow both admins and staff to view their profile details
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_STAFF')) {
            throw $this->createAccessDeniedException('Access Denied. Admin or Staff role required.');
        }

        $user = $this->getUser();

        return $this->render('admin/account/index.html.twig', [
            'user' => $user,
        ]);
    }

    // Helper methods
    private function exportAsJSON(array $data, string $filename): Response
    {
        $response = new Response(json_encode($data, JSON_PRETTY_PRINT));
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '.json"');
        return $response;
    }

    private function exportAsCSV(array $data, string $filename): Response
    {
        $csv = "Field,Value\n";
        foreach ($data as $key => $value) {
            $csv .= '"' . str_replace('"', '""', $key) . '","' . str_replace('"', '""', $value ?? '') . "\"\n";
        }

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '.csv"');
        return $response;
    }

    private function exportAsSQL(array $data, string $filename, $user): Response
    {
        $sql = "-- User Data Backup\n";
        $sql .= "-- Generated on " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "UPDATE `user` SET ";
        $updates = [];
        foreach ($data as $key => $value) {
            $updates[] = "`" . str_replace('_', '_', $key) . "` = '" . addslashes($value ?? '') . "'";
        }
        $sql .= implode(", ", $updates);
        $sql .= " WHERE id = " . $user->getId() . ";\n";

        $response = new Response($sql);
        $response->headers->set('Content-Type', 'application/sql');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '.sql"');
        return $response;
    }

    private function exportAsExcel(array $data, string $filename): Response
    {
        // Create a simple HTML table that Excel can read
        $html = "<table border='1'>\n<tr><th>Field</th><th>Value</th></tr>\n";
        foreach ($data as $key => $value) {
            $html .= "<tr><td>" . htmlspecialchars($key) . "</td><td>" . htmlspecialchars($value ?? '') . "</td></tr>\n";
        }
        $html .= "</table>";

        $response = new Response($html);
        $response->headers->set('Content-Type', 'application/vnd.ms-excel');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '.xls"');
        return $response;
    }
}

