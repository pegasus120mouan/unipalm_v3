document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('smsForm');
    const messageTextarea = document.getElementById('message');
    const charCount = document.getElementById('charCount');
    const phoneInput = document.getElementById('phone_number');
    const sendBtn = document.getElementById('sendBtn');
    const btnText = document.getElementById('btnText');
    const spinner = document.getElementById('spinner');

    // Compteur de caractères
    function updateCharCount() {
        const length = messageTextarea.value.length;
        charCount.textContent = `${length}/1600`;
        
        if (length > 1600) {
            charCount.classList.add('text-red-300');
            charCount.classList.remove('text-white');
        } else if (length > 1400) {
            charCount.classList.add('text-yellow-300');
            charCount.classList.remove('text-white', 'text-red-300');
        } else {
            charCount.classList.add('text-white');
            charCount.classList.remove('text-red-300', 'text-yellow-300');
        }
    }

    // Formatage du numéro de téléphone
    function formatPhoneNumber(value) {
        // Supprime tous les caractères non numériques sauf le +
        let cleaned = value.replace(/[^\d+]/g, '');
        
        // Ajoute le + au début si pas présent et que le numéro commence par un chiffre
        if (cleaned.length > 0 && !cleaned.startsWith('+')) {
            cleaned = '+' + cleaned;
        }
        
        return cleaned;
    }

    // Validation du numéro de téléphone
    function validatePhoneNumber(phoneNumber) {
        const phoneRegex = /^\+\d{10,15}$/;
        return phoneRegex.test(phoneNumber);
    }

    // Validation du formulaire
    function validateForm() {
        const phoneNumber = phoneInput.value.trim();
        const message = messageTextarea.value.trim();
        
        // Reset des styles d'erreur
        phoneInput.classList.remove('border-red-500', 'border-green-500');
        messageTextarea.classList.remove('border-red-500', 'border-green-500');
        
        let isValid = true;
        
        // Validation du téléphone
        if (!phoneNumber) {
            phoneInput.classList.add('border-red-500');
            showToast('Veuillez saisir un numéro de téléphone', 'error');
            isValid = false;
        } else if (!validatePhoneNumber(phoneNumber)) {
            phoneInput.classList.add('border-red-500');
            showToast('Format de numéro invalide (ex: +33123456789)', 'error');
            isValid = false;
        } else {
            phoneInput.classList.add('border-green-500');
        }
        
        // Validation du message
        if (!message) {
            messageTextarea.classList.add('border-red-500');
            showToast('Veuillez saisir un message', 'error');
            isValid = false;
        } else if (message.length > 1600) {
            messageTextarea.classList.add('border-red-500');
            showToast('Le message est trop long (max 1600 caractères)', 'error');
            isValid = false;
        } else {
            messageTextarea.classList.add('border-green-500');
        }
        
        return isValid;
    }

    // Affichage des notifications toast
    function showToast(message, type = 'info') {
        // Supprime les anciens toasts
        const existingToast = document.querySelector('.toast');
        if (existingToast) {
            existingToast.remove();
        }
        
        const toast = document.createElement('div');
        toast.className = `toast fixed top-4 right-4 p-4 rounded-lg text-white z-50 transform transition-all duration-300 translate-x-full`;
        
        const bgColor = type === 'error' ? 'bg-red-500' : type === 'success' ? 'bg-green-500' : 'bg-blue-500';
        toast.classList.add(bgColor);
        
        const icon = type === 'error' ? 'fa-exclamation-circle' : type === 'success' ? 'fa-check-circle' : 'fa-info-circle';
        
        toast.innerHTML = `
            <div class="flex items-center">
                <i class="fas ${icon} mr-3"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(toast);
        
        // Animation d'entrée
        setTimeout(() => {
            toast.classList.remove('translate-x-full');
        }, 100);
        
        // Suppression automatique après 5 secondes
        setTimeout(() => {
            toast.classList.add('translate-x-full');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 300);
        }, 5000);
    }

    // État de chargement du bouton
    function setLoadingState(loading) {
        if (loading) {
            sendBtn.disabled = true;
            sendBtn.classList.add('opacity-75', 'cursor-not-allowed');
            btnText.textContent = 'Envoi en cours...';
            spinner.classList.remove('hidden');
        } else {
            sendBtn.disabled = false;
            sendBtn.classList.remove('opacity-75', 'cursor-not-allowed');
            btnText.textContent = 'Envoyer SMS';
            spinner.classList.add('hidden');
        }
    }

    // Event listeners
    messageTextarea.addEventListener('input', updateCharCount);
    
    phoneInput.addEventListener('input', function() {
        this.value = formatPhoneNumber(this.value);
    });
    
    phoneInput.addEventListener('blur', function() {
        const phoneNumber = this.value.trim();
        if (phoneNumber && validatePhoneNumber(phoneNumber)) {
            this.classList.remove('border-red-500');
            this.classList.add('border-green-500');
        } else if (phoneNumber) {
            this.classList.remove('border-green-500');
            this.classList.add('border-red-500');
        }
    });
    
    messageTextarea.addEventListener('blur', function() {
        const message = this.value.trim();
        if (message && message.length <= 1600) {
            this.classList.remove('border-red-500');
            this.classList.add('border-green-500');
        } else if (message) {
            this.classList.remove('border-green-500');
            this.classList.add('border-red-500');
        }
    });
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (validateForm()) {
            setLoadingState(true);
            
            // Simulation d'un délai pour l'UX
            setTimeout(() => {
                this.submit();
            }, 500);
        }
    });

    // Initialisation
    updateCharCount();
    
    // Auto-focus sur le champ téléphone si vide
    if (!phoneInput.value) {
        phoneInput.focus();
    }
});
