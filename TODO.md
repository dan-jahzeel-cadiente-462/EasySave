# Email-as-Login Implementation TODO

## Overview
- Non-admin users (ROLE_USER, ROLE_STAFF): Login with email (username field populated as email automatically)
- Admin (ROLE_ADMIN): Login with username (email optional)
- Keep username NOT NULL in DB
- Exempt admin from email verification (already handled)

## Steps (in order):


### 1. Update User Entity constraints [COMPLETE]

- Add NotBlank to username
- Ensure email handling


### 2. Update templates [COMPLETE]

- security/login.html.twig: Label to 'Email'
- admin/login.html.twig: Emphasize 'Username'
- registration/register.html.twig: Hide username field or label as 'Email'


### 3. Update Registration [COMPLETE]

- RegistrationFormType.php: Remove/hide username field
- RegistrationController.php: Force $user->setUsername($user->getEmail())

### 4. Update API Registration/Login [PENDING]
- ApiRegistrationController.php: username = email
- ApiLoginController.php: Accept 'email' field

### 5. Update Google OAuth [PENDING]
- GoogleAuthenticator.php: $user->setUsername($email)

### 6. Clear cache & Test [PENDING]
- php bin/console cache:clear
- Test user/staff register/login (email)
- Test admin login (username)
- Test API
- Test Brevo emails (mailer config unchanged)

### 7. Complete [PENDING]
