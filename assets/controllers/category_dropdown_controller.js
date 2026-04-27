import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets = [
    'toggle',
    'panel',
    'searchInput',
    'list',
    'sortName',
    'sortPath',
    'sortProducts',
    'selectedValue',
    'selectedLabel',
    'chevron',
    'emptyState'
  ];

  static values = {
    categories: Array,
    currentCategoryId: { type: Number, default: null }
  };

  connect() {
    // Parse categories from data attribute if not already parsed
    if (this.categoriesValue && typeof this.categoriesValue === 'string') {
      try {
        this.categories = JSON.parse(this.categoriesValue);
      } catch (e) {
        console.error('Failed to parse categories:', e);
        this.categories = [];
      }
    } else {
      this.categories = this.categoriesValue || [];
    }
    
    this.currentSort = 'name';
    this.sortDirection = 'asc';
    this.filteredCategories = [...this.categories];
    
    // Set initial selected label from form value if exists
    const selectElement = this.element.querySelector('select');
    if (selectElement && selectElement.value) {
      const category = this.categories.find(c => c.id == selectElement.value);
      if (category) {
        this.selectedLabelTarget.textContent = category.name;
        this.currentCategoryIdValue = category.id;
      }
    }
    
    this.renderCategories();
  }

  toggle() {
    this.panelTarget.classList.toggle('hidden');
    this.chevronTarget.classList.toggle('rotate-180');
    if (!this.panelTarget.classList.contains('hidden')) {
      this.searchInputTarget.focus();
    }
  }

  search() {
    this.renderCategories();
  }

  sort(event) {
    const sortKey = event.currentTarget.dataset.sort;
    if (this.currentSort === sortKey) {
      this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
      this.currentSort = sortKey;
      this.sortDirection = 'asc';
    }
    this.updateSortUI();
    this.renderCategories();
  }

  select(event) {
    const categoryId = parseInt(event.currentTarget.dataset.categoryId);
    const category = this.categories.find(c => c.id === categoryId);
    
    if (category) {
      // Update hidden select
      const selectElement = this.element.querySelector('select');
      if (selectElement) {
        selectElement.value = categoryId;
        selectElement.dispatchEvent(new Event('change', { bubbles: true }));
      }
      
      // Update label
      this.selectedLabelTarget.textContent = category.name;
      this.currentCategoryIdValue = categoryId;
      
      // Close dropdown
      this.hide();
      
      // Dispatch custom event
      this.dispatch('changed', { detail: { id: categoryId, name: category.name } });
    }
  }

  renderCategories() {
    const query = this.searchInputTarget.value.toLowerCase().trim();
    
    // Filter categories
    this.filteredCategories = this.categories.filter(cat => 
      !query || 
      cat.name.toLowerCase().includes(query) ||
      (cat.path && cat.path.toLowerCase().includes(query)) ||
      (cat.description && cat.description.toLowerCase().includes(query))
    );

    // Sort categories
    this.filteredCategories.sort((a, b) => {
      let aVal, bVal;
      
      switch (this.currentSort) {
        case 'name':
          aVal = a.name.toLowerCase();
          bVal = b.name.toLowerCase();
          break;
        case 'path':
          aVal = (a.path || '').toLowerCase();
          bVal = (b.path || '').toLowerCase();
          break;
        case 'products':
          aVal = a.productCount || 0;
          bVal = b.productCount || 0;
          break;
        default:
          aVal = a.id;
          bVal = b.id;
      }
      
      if (aVal < bVal) return this.sortDirection === 'asc' ? -1 : 1;
      if (aVal > bVal) return this.sortDirection === 'asc' ? 1 : -1;
      return 0;
    });

    // Render items
    this.listTarget.innerHTML = this.filteredCategories
      .map(cat => this.createCategoryItem(cat))
      .join('');

    // Show/hide empty state
    if (this.filteredCategories.length === 0) {
      this.emptyStateTarget.classList.remove('hidden');
      this.listTarget.classList.add('hidden');
    } else {
      this.emptyStateTarget.classList.add('hidden');
      this.listTarget.classList.remove('hidden');
    }
  }

  createCategoryItem(category) {
    const isSelected = category.id === this.currentCategoryIdValue;
    const indent = '&nbsp;&nbsp;'.repeat(category.level || 0);
    const className = isSelected 
      ? 'bg-blue-50 border-l-4 border-blue-500' 
      : 'hover:bg-gray-50 border-l-4 border-transparent';
    
    return `
      <li data-action="click->category-dropdown#select" 
          data-category-id="${category.id}"
          class="px-4 py-3 cursor-pointer ${className} transition-colors flex items-center justify-between group">
        <div class="truncate flex-1">
          <div class="text-sm font-medium text-gray-900">${indent}${this.escapeHtml(category.name)}</div>
          ${category.description ? `<div class="text-xs text-gray-500 mt-0.5">${this.escapeHtml(category.description)}</div>` : ''}
        </div>
        <div class="text-xs text-gray-400 ml-2 whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity">
          ${category.productCount ? `<span class="mr-2">${category.productCount}p</span>` : ''}
          ${category.childrenCount ? `<span>${category.childrenCount}c</span>` : ''}
        </div>
      </li>
    `;
  }

  updateSortUI() {
    // Reset all buttons
    [this.sortNameTarget, this.sortPathTarget, this.sortProductsTarget].forEach(btn => {
      btn.classList.remove('bg-blue-50', 'text-blue-700', 'border-blue-300');
      btn.classList.add('bg-white', 'text-gray-700', 'border-gray-300');
    });
    
    // Highlight active sort button
    const activeBtn = this.currentSort === 'name' ? this.sortNameTarget : 
                     this.currentSort === 'path' ? this.sortPathTarget : 
                     this.sortProductsTarget;
    
    if (activeBtn) {
      activeBtn.classList.remove('bg-white', 'text-gray-700', 'border-gray-300');
      activeBtn.classList.add('bg-blue-50', 'text-blue-700', 'border-blue-300');
      
      // Update sort icon for all buttons
      [this.sortNameTarget, this.sortPathTarget, this.sortProductsTarget].forEach(btn => {
        const icon = btn.querySelector('.sort-icon');
        const sortType = btn.dataset.sort;
        if (sortType === this.currentSort) {
          icon.textContent = this.sortDirection === 'asc' ? '↥' : '↧';
          icon.classList.remove('text-gray-500');
          icon.classList.add('text-blue-600');
        } else {
          icon.textContent = '↕';
          icon.classList.remove('text-blue-600');
          icon.classList.add('text-gray-500');
        }
      });
    }
  }

  hide() {
    this.panelTarget.classList.add('hidden');
    this.chevronTarget.classList.remove('rotate-180');
  }

  escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
}


