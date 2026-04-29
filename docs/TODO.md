# Landing Page Layout & Appearance Fixes

## Step 1 — Fix broken HTML structure in About page
- [x] Close missing `</div>` for stats grid before "Our Core Values" section

## Step 2 — Fix body background conflicts (wrong bg showing)
- [x] Remove `background-color: lightgray` from `assets/styles/app.css`
- [x] Remove conflicting `body { background: ... }` from `assets/styles/animations.css`

## Step 3 — Fix mobile header dark-in-light issue + dark mode flash
- [x] Add early dark-mode script to `base.html.twig` `<head>` (pre-render)
- [x] Clean up navbar dual styling (Tailwind + custom CSS conflict)

## Step 4 — Fix inline styles blocking dark mode
- [x] Fix about page search input `background: white` overriding dark mode
- [x] Fix hardcoded inline-style badges/CTAs without dark variants
- [x] Fix contact page feedback alerts without dark variants

## Step 5 — Final polish
- [x] Fix invalid `focus:ring-color` inline style
- [x] Fix invalid `ring-color` inline styles in teams section
- [x] Ensure consistent dark mode across all landing pages

