/**
 * Script utilitaire pour gérer l'historique du navigateur avec les modals Bootstrap
 * Corrige le problème où les modals restent ouverts quand on utilise la flèche retour
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Nettoyer l'URL des paramètres de succès/erreur après affichage
    if (window.location.search.includes('paiement_success=1') || 
        window.location.search.includes('paiement_error=1') ||
        window.location.search.includes('success=1') ||
        window.location.search.includes('error=1')) {
        
        // Remplacer l'URL sans les paramètres après 3 secondes
        setTimeout(function() {
            const url = new URL(window.location);
            url.searchParams.delete('paiement_success');
            url.searchParams.delete('paiement_error');
            url.searchParams.delete('success');
            url.searchParams.delete('error');
            window.history.replaceState({}, '', url.toString());
        }, 3000);
    }

    // Fonction pour fermer tous les modals ouverts
    function closeAllModals() {
        const openModals = document.querySelectorAll('.modal.show');
        openModals.forEach(function(modal) {
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
                bsModal.hide();
            }
        });
        
        // Supprimer les backdrops qui peuvent rester
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(function(backdrop) {
            backdrop.remove();
        });
        
        // Restaurer l'état normal du body
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }

    // Gestion de l'événement pageshow (quand on revient sur la page)
    window.addEventListener('pageshow', function(event) {
        closeAllModals();
    });

    // Gestion de l'événement popstate (bouton retour/avant)
    window.addEventListener('popstate', function(event) {
        closeAllModals();
    });

    // Ajouter un état à l'historique quand un modal s'ouvre
    document.addEventListener('shown.bs.modal', function(event) {
        // Ajouter un état à l'historique pour permettre la fermeture avec le bouton retour
        if (window.history.state !== 'modal-open') {
            window.history.pushState('modal-open', '', window.location.href);
        }
    });

    // Gérer la fermeture des modals avec le bouton retour
    let modalWasOpen = false;
    
    document.addEventListener('shown.bs.modal', function(event) {
        modalWasOpen = true;
    });
    
    document.addEventListener('hidden.bs.modal', function(event) {
        modalWasOpen = false;
    });

    // Intercepter le bouton retour pour fermer les modals
    window.addEventListener('popstate', function(event) {
        const openModals = document.querySelectorAll('.modal.show');
        
        if (openModals.length > 0) {
            // Il y a des modals ouverts, les fermer
            closeAllModals();
            
            // Empêcher le retour en arrière en ajoutant un nouvel état
            window.history.pushState('modal-closed', '', window.location.href);
        }
    });

    // Gestion spéciale pour les formulaires de paiement
    document.querySelectorAll('form[action*="save_paiement"]').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            // Ajouter un indicateur que le formulaire a été soumis
            sessionStorage.setItem('formSubmitted', 'true');
        });
    });

    // Vérifier si on revient après une soumission de formulaire
    if (sessionStorage.getItem('formSubmitted') === 'true') {
        sessionStorage.removeItem('formSubmitted');
        
        // S'assurer que tous les modals sont fermés
        setTimeout(function() {
            closeAllModals();
        }, 100);
    }
});

// Fonction utilitaire exportée pour fermer manuellement tous les modals
window.closeAllModals = function() {
    const openModals = document.querySelectorAll('.modal.show');
    openModals.forEach(function(modal) {
        const bsModal = bootstrap.Modal.getInstance(modal);
        if (bsModal) {
            bsModal.hide();
        }
    });
    
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(function(backdrop) {
        backdrop.remove();
    });
    
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
};
