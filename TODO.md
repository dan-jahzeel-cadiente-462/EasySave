# Fix Category Edit Issue

## Problem
- Unable to update product information: Unable to save changes when editing categories
- Likely due to circular reference issues when setting parent categories

## Changes Made

### 1. CategoryRepository.php
- Added `getDescendantIds()` method to get all descendant category IDs including itself
- Added `collectDescendants()` helper method for recursive collection

### 2. CategoryType.php
- Modified parent field query_builder to exclude the category itself and all its descendants
- This prevents selecting invalid parents that would create circular references

### 3. Category.php
- Added validation imports (Assert and ExecutionContextInterface)
- Added `validateParent()` method with callback validation
- Added Assert\Callback constraint to parent property
- Validation checks for self-parent and circular references

## Testing
- Run the application and test editing categories
- Ensure no circular references can be created
- Verify that editing categories works without errors
