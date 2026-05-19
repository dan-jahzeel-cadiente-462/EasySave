# Frontend Structure Improvement Plan
**Target:** User and Landing Pages (based on FRONTEND_STRUCTURE_REPORT.md)

---

## 1. Information Gathered

### Current Template Structure:
- **Landing Pages:** Good dark mode support, mobile menu, animations
- **User Pages:** Missing dark mode, no mobile menu, inconsistent styling

### Key Files Identified:
1. `templates/base.html.twig` - Main public layout (✅ dark mode)
2. `templates/landing-navbar.html.twig` - Landing nav (✅ dark mode, mobile menu)
3. `templates/user/base.html.twig` - User layout (❌ no dark mode)
4. `templates/partials/user/user_header.html.twig` - User nav (❌ no dark mode, no mobile menu)
5. `templates/user/shop/index.html.twig` - Shop page (partial dark mode)
6. `templates/home/index.html.twig` - Home page (✅ dark mode)

---

## 2. Improvement Plan

### Phase 1: User Header Enhancements
**File:** `templates/partials/user/user_header.html.twig`

**Changes:**
1. Add dark mode support:
   - Add `dark:` prefix to all color classes
   - Add dark mode toggle button with sun/moon icons
   - Add Alpine.js for dark mode state management
   - Use localStorage for persistence

2. Add mobile menu:
   - Add mobile menu toggle button
   - Add collapsible mobile navigation dropdown
   - Add smooth transitions

3. Add sticky positioning:
   - Make header sticky with `sticky top-0 z-50`

4. Improve styling:
   - Consistent green color scheme
   - Better hover states
   - Add focus states for accessibility

### Phase 2: User Base Layout
**File:** `templates/user/base.html.twig`

**Changes:**
1. Add dark mode initialization script (copy from base.html.twig)
2. Add dark mode classes to body
3. Add gradient background support
4. Ensure consistent font loading

### Phase 3: Shop Page Consistency
**File:** `templates/user/shop/index.html.twig`

**Changes:**
1. Replace indigo with consistent green color scheme
2. Ensure all dark mode classes are properly applied
3. Add proper loading animations

### Phase 4: Testing
- Test dark mode toggle on all user pages
- Test mobile responsiveness
- Verify navigation consistency

---

## 3. Dependent Files

These files need to be edited:
1. `templates/partials/user/user_header.html.twig`
2. `templates/user/base.html.twig`
3. `templates/user/shop/includes/_product_grid_card.html.twig`
4. `templates/user/shop/includes/_product_list_card.html.twig`

---

## 4. Implementation Steps

### Step 1: Update User Header
- Add Alpine.js dark mode state
- Add dark mode toggle button
- Add mobile menu toggle
- Apply dark mode classes throughout

### Step 2: Update User Base Layout
- Copy dark mode init script from base.html.twig
- Add dark: color classes
- Add gradient-bg class support

### Step 3: Update Shop Components
- Replace indigo with green for consistency
- Ensure all cards use consistent dark mode

### Step 4: Test All Changes
- Verify dark mode works on all pages
- Test mobile navigation
- Check cross-browser compatibility

---

## 5. Follow-up Steps

After implementation:
1. Run `npm run build` to compile assets
2. Clear Symfony cache
3. Test in development environment
4. Verify all user pages render correctly in dark/light mode

---

**Status:** Ready for implementation
**Priority:** High - Dark mode consistency across all pages
