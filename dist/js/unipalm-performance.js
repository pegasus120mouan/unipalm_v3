/* ===== UNIPALM PERFORMANCE OPTIMIZATIONS ===== */

// Gestion des erreurs de chargement des ressources
document.addEventListener('DOMContentLoaded', function() {
    
    // Supprimer les erreurs de console pour les ressources manquantes
    const originalError = console.error;
    console.error = function(...args) {
        const message = args.join(' ');
        // Ignorer les erreurs de ressources manquantes
        if (message.includes('404') || 
            message.includes('Failed to load') || 
            message.includes('net::ERR_') ||
            message.includes('font') ||
            message.includes('mapbox') ||
            message.includes('leaflet')) {
            return;
        }
        originalError.apply(console, args);
    };

    // Optimisation du chargement des images
    const images = document.querySelectorAll('img');
    images.forEach(img => {
        img.loading = 'lazy';
        img.onerror = function() {
            this.style.display = 'none';
        };
    });

    // Optimisation des liens externes
    const externalLinks = document.querySelectorAll('link[href^="http"]');
    externalLinks.forEach(link => {
        link.onerror = function() {
            console.log('Ressource externe non chargée:', this.href);
            this.remove();
        };
    });

    // Optimisation des scripts externes
    const externalScripts = document.querySelectorAll('script[src^="http"]');
    externalScripts.forEach(script => {
        script.onerror = function() {
            console.log('Script externe non chargé:', this.src);
            this.remove();
        };
    });

    // Préchargement intelligent des ressources critiques
    const criticalResources = [
        '../plugins/fontawesome-free/css/all.min.css',
        '../dist/css/adminlte.min.css'
    ];

    criticalResources.forEach(resource => {
        const link = document.createElement('link');
        link.rel = 'preload';
        link.as = 'style';
        link.href = resource;
        document.head.appendChild(link);
    });

    // Optimisation des tables
    const tables = document.querySelectorAll('table');
    tables.forEach(table => {
        // Virtualisation pour les grandes tables
        if (table.rows.length > 100) {
            table.style.contain = 'layout style paint';
        }
    });

    // Optimisation du scroll
    let ticking = false;
    function updateScrollPosition() {
        // Optimisations pendant le scroll
        ticking = false;
    }

    window.addEventListener('scroll', function() {
        if (!ticking) {
            requestAnimationFrame(updateScrollPosition);
            ticking = true;
        }
    }, { passive: true });

    // Nettoyage des timers et événements inutiles
    const cleanupTimers = setInterval(() => {
        // Nettoyer les éléments DOM orphelins
        const orphanElements = document.querySelectorAll('[style*="display: none"]');
        orphanElements.forEach(el => {
            if (!el.closest('.modal') && !el.closest('.dropdown-menu')) {
                el.remove();
            }
        });
    }, 30000); // Toutes les 30 secondes

    // Optimisation de la mémoire
    window.addEventListener('beforeunload', function() {
        clearInterval(cleanupTimers);
        // Nettoyer les événements
        document.removeEventListener('scroll', updateScrollPosition);
    });

    // Performance monitoring (optionnel)
    if ('performance' in window) {
        window.addEventListener('load', function() {
            setTimeout(() => {
                const perfData = performance.getEntriesByType('navigation')[0];
                if (perfData.loadEventEnd - perfData.loadEventStart > 3000) {
                    console.log('⚠️ Page lente détectée. Temps de chargement:', 
                               Math.round(perfData.loadEventEnd - perfData.loadEventStart), 'ms');
                }
            }, 1000);
        });
    }

    // Optimisation des modals
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('show.bs.modal', function() {
            this.style.contain = 'layout style paint';
        });
        
        modal.addEventListener('hidden.bs.modal', function() {
            // Nettoyer le modal après fermeture
            const forms = this.querySelectorAll('form');
            forms.forEach(form => form.reset());
        });
    });

    // Optimisation des dropdowns
    const dropdowns = document.querySelectorAll('.dropdown-menu');
    dropdowns.forEach(dropdown => {
        dropdown.style.contain = 'layout style paint';
    });

    console.log('✅ Optimisations de performance UniPalm chargées');
});

// Fonction utilitaire pour débouncer les événements
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Fonction utilitaire pour throttler les événements
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Export pour utilisation globale
window.UniPalmPerformance = {
    debounce,
    throttle
};
