/**
 * Script utilitaire pour afficher un loader pendant les paiements
 * Similaire au système utilisé dans tickets_attente.php
 */

class PaymentLoader {
    constructor() {
        this.loader = null;
        this.isVisible = false;
    }

    // Créer le loader
    create() {
        if (this.loader) {
            return this.loader;
        }

        this.loader = document.createElement('div');
        this.loader.id = 'paymentLoader';
        this.loader.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            display: none;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 10000;
            backdrop-filter: blur(5px);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        `;
        
        this.loader.innerHTML = `
            <div style="
                width: 80px;
                height: 80px;
                border: 4px solid rgba(0,123,255,0.2);
                border-top: 4px solid #007bff;
                border-radius: 50%;
                animation: paymentSpin 1s linear infinite;
                margin-bottom: 20px;
            "></div>
            <div style="
                color: #007bff;
                font-size: 18px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 2px;
                text-align: center;
                margin-bottom: 10px;
            ">Traitement du paiement...</div>
            <div style="
                color: #6c757d;
                font-size: 14px;
                text-align: center;
                max-width: 300px;
                line-height: 1.4;
            ">Veuillez patienter, ne fermez pas cette page</div>
            <div style="
                margin-top: 20px;
                width: 200px;
                height: 4px;
                background: rgba(0,123,255,0.2);
                border-radius: 2px;
                overflow: hidden;
            ">
                <div style="
                    width: 100%;
                    height: 100%;
                    background: linear-gradient(90deg, #007bff, #0056b3, #007bff);
                    animation: paymentProgress 2s ease-in-out infinite;
                "></div>
            </div>
        `;
        
        // Ajouter les animations CSS si elles n'existent pas
        if (!document.getElementById('paymentLoaderStyles')) {
            const style = document.createElement('style');
            style.id = 'paymentLoaderStyles';
            style.textContent = `
                @keyframes paymentSpin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                @keyframes paymentProgress {
                    0% { transform: translateX(-100%); }
                    50% { transform: translateX(0%); }
                    100% { transform: translateX(100%); }
                }
            `;
            document.head.appendChild(style);
        }
        
        document.body.appendChild(this.loader);
        return this.loader;
    }

    // Afficher le loader
    show(message = 'Traitement du paiement...') {
        if (!this.loader) {
            this.create();
        }
        
        // Mettre à jour le message si fourni
        const messageElement = this.loader.querySelector('div:nth-child(2)');
        if (messageElement && message) {
            messageElement.textContent = message;
        }
        
        this.loader.style.display = 'flex';
        this.isVisible = true;
        
        // Fermer tous les modals Bootstrap ouverts
        const openModals = document.querySelectorAll('.modal.show');
        openModals.forEach(modal => {
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
                bsModal.hide();
            }
        });
        
        // Empêcher le scroll du body
        document.body.style.overflow = 'hidden';
    }

    // Masquer le loader
    hide() {
        if (this.loader) {
            this.loader.style.display = 'none';
            this.isVisible = false;
        }
        
        // Restaurer le scroll du body
        document.body.style.overflow = '';
    }

    // Vérifier si le loader est visible
    isShowing() {
        return this.isVisible;
    }

    // Détruire le loader
    destroy() {
        if (this.loader && this.loader.parentNode) {
            this.loader.parentNode.removeChild(this.loader);
            this.loader = null;
            this.isVisible = false;
        }
        
        // Supprimer les styles si plus de loader
        const styles = document.getElementById('paymentLoaderStyles');
        if (styles && !document.getElementById('paymentLoader')) {
            styles.remove();
        }
    }
}

// Instance globale
window.paymentLoader = new PaymentLoader();

// Fonctions utilitaires globales pour compatibilité
window.showPaymentLoader = function(message) {
    window.paymentLoader.show(message);
};

window.hidePaymentLoader = function() {
    window.paymentLoader.hide();
};

// Auto-initialisation pour les formulaires de paiement
document.addEventListener('DOMContentLoaded', function() {
    // Gérer tous les formulaires qui pointent vers save_paiement_agent.php
    document.querySelectorAll('form[action*="save_paiement"]').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            // Afficher le loader
            window.paymentLoader.show();
            
            // Désactiver le bouton de soumission
            const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                const originalText = submitButton.innerHTML || submitButton.value;
                submitButton.setAttribute('data-original-text', originalText);
                
                if (submitButton.tagName === 'BUTTON') {
                    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Traitement...';
                } else {
                    submitButton.value = 'Traitement...';
                }
            }
            
            // Sécurité : masquer le loader après 30 secondes
            setTimeout(function() {
                window.paymentLoader.hide();
                if (submitButton) {
                    submitButton.disabled = false;
                    const originalText = submitButton.getAttribute('data-original-text');
                    if (originalText) {
                        if (submitButton.tagName === 'BUTTON') {
                            submitButton.innerHTML = originalText;
                        } else {
                            submitButton.value = originalText;
                        }
                    }
                }
            }, 30000);
        });
    });
    
    // Masquer le loader au chargement de la page
    window.paymentLoader.hide();
    
    // Masquer le loader lors des événements de navigation
    window.addEventListener('pageshow', function() {
        window.paymentLoader.hide();
    });
    
    window.addEventListener('popstate', function() {
        window.paymentLoader.hide();
    });
});
