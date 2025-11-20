/* ===== OPTIMISATIONS SPÉCIFIQUES AUX PAGES TICKETS ===== */

$(document).ready(function() {
    
    // Optimisation des DataTables si présentes
    if ($.fn.DataTable) {
        // Configuration optimisée pour les DataTables
        $.extend(true, $.fn.dataTable.defaults, {
            "processing": true,
            "serverSide": false, // Désactivé car nous utilisons la pagination SQL
            "deferRender": true,
            "scrollCollapse": true,
            "stateSave": false, // Désactivé pour éviter les problèmes de localStorage
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/French.json"
            },
            "pageLength": 15,
            "lengthMenu": [15, 25, 50, 100],
            "dom": '<"row"<"col-sm-6"l><"col-sm-6"f>>rtip',
            "columnDefs": [
                {
                    "targets": "_all",
                    "className": "text-center"
                }
            ]
        });
    }

    // Optimisation des formulaires de filtrage
    const filterForms = document.querySelectorAll('form[method="GET"]');
    filterForms.forEach(form => {
        // Debounce pour les champs de recherche
        const searchInputs = form.querySelectorAll('input[type="search"], input[name*="numero"]');
        searchInputs.forEach(input => {
            let timeout;
            input.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    // Auto-submit après 500ms d'inactivité
                    if (this.value.length >= 3 || this.value.length === 0) {
                        form.submit();
                    }
                }, 500);
            });
        });
    });

    // Optimisation des Select2
    if ($.fn.select2) {
        $('.select2').select2({
            theme: 'bootstrap4',
            width: '100%',
            placeholder: 'Sélectionner...',
            allowClear: true,
            minimumResultsForSearch: 10,
            escapeMarkup: function(markup) {
                return markup;
            }
        });
    }

    // Optimisation du chargement des images d'avatar
    const avatars = document.querySelectorAll('img[src*="user"], img[src*="avatar"]');
    avatars.forEach(img => {
        img.loading = 'lazy';
        img.onerror = function() {
            // Remplacer par un avatar par défaut
            this.src = '../dist/img/user2-160x160.jpg';
        };
    });

    // Optimisation des modals de tickets
    const ticketModals = document.querySelectorAll('.modal[id*="ticket"], .modal[id*="edit"], .modal[id*="delete"]');
    ticketModals.forEach(modal => {
        modal.addEventListener('show.bs.modal', function(e) {
            // Optimiser le rendu du modal
            this.style.contain = 'layout style paint';
            
            // Pré-remplir les données si nécessaire
            const button = e.relatedTarget;
            if (button) {
                const ticketId = button.getAttribute('data-ticket-id');
                if (ticketId) {
                    // Charger les données du ticket de manière asynchrone
                    loadTicketData(ticketId, this);
                }
            }
        });
        
        modal.addEventListener('hidden.bs.modal', function() {
            // Nettoyer le modal après fermeture
            const forms = this.querySelectorAll('form');
            forms.forEach(form => form.reset());
            
            // Supprimer les styles d'optimisation
            this.style.contain = '';
        });
    });

    // Fonction pour charger les données de ticket de manière optimisée
    function loadTicketData(ticketId, modal) {
        // Utiliser un cache simple pour éviter les requêtes répétées
        if (!window.ticketCache) {
            window.ticketCache = new Map();
        }
        
        if (window.ticketCache.has(ticketId)) {
            populateModal(modal, window.ticketCache.get(ticketId));
            return;
        }
        
        // Charger les données via AJAX (si nécessaire)
        // Cette partie peut être implémentée selon les besoins
        console.log('Chargement des données pour le ticket:', ticketId);
    }

    function populateModal(modal, data) {
        // Remplir le modal avec les données
        const inputs = modal.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            if (data[input.name]) {
                input.value = data[input.name];
            }
        });
    }

    // Optimisation des boutons d'action
    const actionButtons = document.querySelectorAll('.btn[data-toggle="modal"]');
    actionButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Ajouter un indicateur de chargement
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Chargement...';
            this.disabled = true;
            
            // Restaurer après un délai
            setTimeout(() => {
                this.innerHTML = originalText;
                this.disabled = false;
            }, 1000);
        });
    });

    // Optimisation des alertes
    const alerts = document.querySelectorAll('.alert[data-auto-dismiss]');
    alerts.forEach(alert => {
        setTimeout(() => {
            $(alert).fadeOut(500, function() {
                this.remove();
            });
        }, 5000);
    });

    // Optimisation du scroll infini pour les grandes listes (optionnel)
    const ticketTables = document.querySelectorAll('table.table-tickets');
    ticketTables.forEach(table => {
        if (table.rows.length > 50) {
            // Implémenter la virtualisation pour les grandes tables
            virtualizeTable(table);
        }
    });

    function virtualizeTable(table) {
        // Virtualisation simple pour améliorer les performances
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.rows);
        const visibleRows = 20;
        let startIndex = 0;

        function updateVisibleRows() {
            rows.forEach((row, index) => {
                if (index >= startIndex && index < startIndex + visibleRows) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Ajouter la pagination virtuelle
        const pagination = document.createElement('div');
        pagination.className = 'virtual-pagination mt-3';
        pagination.innerHTML = `
            <button class="btn btn-sm btn-outline-primary" id="prev-virtual">Précédent</button>
            <span class="mx-3">Lignes ${startIndex + 1} - ${Math.min(startIndex + visibleRows, rows.length)} sur ${rows.length}</span>
            <button class="btn btn-sm btn-outline-primary" id="next-virtual">Suivant</button>
        `;
        
        table.parentNode.appendChild(pagination);

        document.getElementById('prev-virtual').addEventListener('click', () => {
            if (startIndex > 0) {
                startIndex -= visibleRows;
                updateVisibleRows();
            }
        });

        document.getElementById('next-virtual').addEventListener('click', () => {
            if (startIndex + visibleRows < rows.length) {
                startIndex += visibleRows;
                updateVisibleRows();
            }
        });

        updateVisibleRows();
    }

    // Optimisation des filtres en temps réel
    const filterInputs = document.querySelectorAll('input[data-filter]');
    filterInputs.forEach(input => {
        input.addEventListener('input', UniPalmPerformance.debounce(function() {
            const filterValue = this.value.toLowerCase();
            const targetTable = document.querySelector(this.dataset.filter);
            
            if (targetTable) {
                const rows = targetTable.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(filterValue) ? '' : 'none';
                });
            }
        }, 300));
    });

    console.log('✅ Optimisations tickets chargées');
});
