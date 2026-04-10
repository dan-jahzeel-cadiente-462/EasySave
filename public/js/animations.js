// public/js/animations.js

(function() {
    'use strict';

    /**
     * Ensures animations work properly on page load, refresh, and bfcache restore
     */
    
    // Utility function to restart animation on an element
    function restartAnimation(element, className) {
        if (!element) return;
        
        // Remove the class
        element.classList.remove(className);
        
        // Force reflow to restart animation
        void element.offsetHeight;
        
        // Re-add the class
        element.classList.add(className);
    }
    
    // Utility function to trigger navbar animation
    function triggerNavbarAnimation() {
        const navbar = document.querySelector('nav');
        if (navbar && !navbar.classList.contains('animate-navbar-slide')) {
            navbar.classList.add('animate-navbar-slide');
            navbar.addEventListener('animationend', () => {
                navbar.classList.remove('animate-navbar-slide');
            }, { once: true });
        }
    }
    
    // Trigger content animation
    function triggerContentAnimation() {
        const contentContainer = document.querySelector('main .animate-content');
        if (contentContainer) {
            restartAnimation(contentContainer, 'animate-content');
        }
    }
    
    // Handle page load / refresh
    function initializeAnimations() {
        // Ensure content has the animation class
        const contentContainer = document.querySelector('main .animate-content');
        if (contentContainer && !contentContainer.classList.contains('animate-content')) {
            contentContainer.classList.add('animate-content');
        }
        
        // Trigger navbar animation if not already animating
        triggerNavbarAnimation();
    }
    
    // Handle back-forward cache (pageshow event)
    window.addEventListener('pageshow', function(event) {
        // If the page is loaded from bfcache (persisted), re-trigger animations
        if (event.persisted) {
            triggerNavbarAnimation();
            triggerContentAnimation();
        }
    });
    
    // Handle Turbo/Stimulus or any AJAX navigation if needed
    document.addEventListener('turbo:load', function() {
        triggerNavbarAnimation();
        triggerContentAnimation();
    });
    
    // Handle standard DOM content loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeAnimations);
    } else {
        initializeAnimations();
    }
    
    // Optional: Log to console for debugging
    if (process.env.NODE_ENV !== 'production') {
        console.log('✅ Animation system ready: triggers on refresh, first load, and bfcache restore');
    }
})();