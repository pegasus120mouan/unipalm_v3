<?php
require_once '../inc/functions/connexion.php';
include('header.php');
?>

<style>
:root {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --danger-gradient: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
    --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --card-shadow: 0 10px 40px rgba(0,0,0,0.1);
    --border-radius: 16px;
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.page-header-doublons {
    background: var(--danger-gradient);
    border-radius: var(--border-radius);
    padding: 30px 40px;
    margin-bottom: 30px;
    color: white;
    box-shadow: var(--card-shadow);
    position: relative;
    overflow: hidden;
}

.page-header-doublons::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 400px;
    height: 400px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
}

.page-header-doublons h1 {
    margin: 0;
    font-size: 1.8rem;
    font-weight: 700;
    position: relative;
    z-index: 1;
}

.page-header-doublons h1 i {
    margin-right: 15px;
}

/* Stats Cards */
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 25px;
    box-shadow: var(--card-shadow);
    display: flex;
    align-items: center;
    transition: var(--transition);
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-icon {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 20px;
}

.stat-icon.danger {
    background: var(--danger-gradient);
}

.stat-icon.warning {
    background: var(--warning-gradient);
}

.stat-icon.info {
    background: var(--info-gradient);
}

.stat-icon i {
    font-size: 28px;
    color: white;
}

.stat-content h3 {
    font-size: 2rem;
    font-weight: 700;
    color: #2c3e50;
    margin: 0;
}

.stat-content p {
    color: #6c757d;
    margin: 0;
    font-size: 0.95rem;
}

/* Table Container */
.table-container {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--card-shadow);
    overflow: hidden;
}

.table-header {
    background: var(--primary-gradient);
    color: white;
    padding: 20px 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.table-header h5 {
    margin: 0;
    font-weight: 700;
    font-size: 1.1rem;
}

.table-header h5 i {
    margin-right: 10px;
}

.table-body {
    padding: 25px;
}

/* Groupe de doublons */
.doublon-group {
    background: #f8f9fa;
    border-radius: 12px;
    margin-bottom: 20px;
    overflow: hidden;
    border-left: 4px solid #eb3349;
}

.doublon-group-header {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    color: white;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.doublon-group-header h6 {
    margin: 0;
    font-weight: 600;
}

.doublon-group-header .badge {
    background: var(--danger-gradient);
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 0.85rem;
}

.doublon-group-body {
    padding: 0;
}

/* Table styling */
.table-doublons {
    margin: 0;
    width: 100%;
}

.table-doublons thead th {
    background: #e9ecef;
    color: #2c3e50;
    font-weight: 600;
    padding: 12px 15px;
    border: none;
    font-size: 0.85rem;
    text-transform: uppercase;
}

.table-doublons tbody tr {
    transition: var(--transition);
}

.table-doublons tbody tr:hover {
    background: #fff3cd;
}

.table-doublons tbody td {
    padding: 15px;
    vertical-align: middle;
    border-bottom: 1px solid #e9ecef;
}

.table-doublons tbody tr:last-child td {
    border-bottom: none;
}

/* Action buttons */
.btn-action {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none;
    transition: var(--transition);
    margin: 0 3px;
}

.btn-action.view {
    background: var(--info-gradient);
    color: white;
}

.btn-action.keep {
    background: var(--success-gradient);
    color: white;
}

.btn-action.delete {
    background: var(--danger-gradient);
    color: white;
}

.btn-action:hover {
    transform: scale(1.1);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

/* Badge styling */
.badge-original {
    background: var(--success-gradient);
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-doublon {
    background: var(--danger-gradient);
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-state i {
    font-size: 80px;
    color: #27ae60;
    margin-bottom: 20px;
}

.empty-state h4 {
    color: #2c3e50;
    font-weight: 700;
    margin-bottom: 10px;
}

.empty-state p {
    color: #6c757d;
}

/* Modal styling */
.modal-confirm .modal-content {
    border-radius: 15px;
    border: none;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

.modal-confirm .modal-header {
    background: var(--danger-gradient);
    border-radius: 15px 15px 0 0;
    border: none;
    color: white;
}

.modal-confirm .modal-body {
    padding: 30px;
    text-align: center;
}

.modal-confirm .modal-footer {
    border: none;
    justify-content: center;
    padding: 20px 30px 30px;
    gap: 15px;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header-doublons {
        padding: 20px;
    }
    
    .page-header-doublons h1 {
        font-size: 1.4rem;
    }
    
    .stats-container {
        grid-template-columns: 1fr;
    }
}

/* Animation */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.doublon-group {
    animation: fadeInUp 0.4s ease-out;
}
</style>

<section class="content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="page-header-doublons">
            <div class="d-flex justify-content-between align-items-center">
                <h1><i class="fas fa-clone"></i>Gestion des Planteurs en Double</h1>
                <a href="plantations.php" class="btn btn-light">
                    <i class="fas fa-arrow-left mr-2"></i>Retour à la liste
                </a>
            </div>
        </div>

        <!-- Error Alert -->
        <div id="errorAlert" class="alert alert-danger" style="border-radius: 12px; display: none;">
            <i class="fas fa-exclamation-triangle mr-2"></i><span id="errorMessage"></span>
        </div>

        <!-- Loader -->
        <div id="loader" class="text-center" style="padding: 60px;">
            <div class="loader-spinner" style="width: 60px; height: 60px; border: 4px solid #f3f3f3; border-top: 4px solid #eb3349; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto;"></div>
            <p class="mt-3 text-muted">Chargement des doublons...</p>
        </div>

        <!-- Stats Cards -->
        <div class="stats-container" id="statsContainer" style="display: none;">
            <div class="stat-card">
                <div class="stat-icon danger">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3 id="statTotalDoublons">0</h3>
                    <p>Planteurs en double</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="stat-content">
                    <h3 id="statTotalGroupes">0</h3>
                    <p>Groupes de doublons</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-trash-alt"></i>
                </div>
                <div class="stat-content">
                    <h3 id="statASupprimer">0</h3>
                    <p>À supprimer (estimation)</p>
                </div>
            </div>
        </div>

        <!-- Table Container -->
        <div class="table-container" id="tableContainer" style="display: none;">
            <div class="table-header">
                <h5><i class="fas fa-list"></i>Liste des doublons détectés</h5>
                <span class="badge badge-light" id="badgeGroupes">0 groupe(s)</span>
            </div>
            <div class="table-body" id="doublonsContent">
                <!-- Contenu généré par JavaScript -->
            </div>
        </div>
    </div>
</section>

<!-- Modal de confirmation de suppression -->
<div class="modal fade modal-confirm" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle mr-2"></i>Confirmation de suppression</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-trash-alt" style="font-size: 32px; color: #eb3349;"></i>
                </div>
                <h4 style="color: #2c3e50; font-weight: 600;">Supprimer ce planteur ?</h4>
                <p style="color: #6c757d;">
                    Êtes-vous sûr de vouloir supprimer <strong id="deleteName" style="color: #eb3349;"></strong> ?<br>
                    <small>Cette action est irréversible.</small>
                </p>
                <input type="hidden" id="deleteId" value="">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" style="padding: 10px 30px; border-radius: 8px;">
                    <i class="fas fa-times mr-2"></i>Annuler
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn" style="padding: 10px 30px; border-radius: 8px; background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%); border: none;">
                    <i class="fas fa-trash-alt mr-2"></i>Supprimer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const apiUrl = '../inc/functions/requete/api_requete_planteurs.php';
    
    // Éléments DOM
    const loader = document.getElementById('loader');
    const errorAlert = document.getElementById('errorAlert');
    const errorMessage = document.getElementById('errorMessage');
    const statsContainer = document.getElementById('statsContainer');
    const tableContainer = document.getElementById('tableContainer');
    const doublonsContent = document.getElementById('doublonsContent');
    
    // Fonctions utilitaires
    function escapeHtml(text) {
        if (!text) return '-';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function formatDate(dateStr) {
        if (!dateStr) return '-';
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('fr-FR');
    }
    
    function showError(msg) {
        loader.style.display = 'none';
        errorMessage.textContent = msg;
        errorAlert.style.display = 'block';
    }
    
    // Générer le HTML pour un groupe de doublons
    function renderGroup(groupe, index) {
        const firstPlanteur = groupe[0];
        const nom = escapeHtml(firstPlanteur.nom_prenoms);
        const tel = firstPlanteur.telephone ? `(${escapeHtml(firstPlanteur.telephone)})` : '';
        
        let rowsHtml = '';
        groupe.forEach((p, i) => {
            const collecteur = p.collecteur 
                ? `${p.collecteur.nom || ''} ${p.collecteur.prenoms || ''}`.trim() 
                : '-';
            const region = p.exploitation?.region || '-';
            const sousPref = p.exploitation?.sous_prefecture_village || '-';
            const village = p.exploitation?.village || '-';
            
            const statusBadge = i === 0 
                ? '<span class="badge-original"><i class="fas fa-star mr-1"></i>Original</span>'
                : '<span class="badge-doublon"><i class="fas fa-copy mr-1"></i>Doublon</span>';
            
            const actionBtn = i === 0
                ? '<button type="button" class="btn-action keep" title="Conserver" disabled><i class="fas fa-check"></i></button>'
                : `<button type="button" class="btn-action delete" title="Supprimer ce doublon" onclick="confirmDelete('${p.id}', '${escapeHtml(p.nom_prenoms).replace(/'/g, "\\'")}')"><i class="fas fa-trash"></i></button>`;
            
            rowsHtml += `
                <tr>
                    <td>${statusBadge}</td>
                    <td><strong>${escapeHtml(p.numero_fiche)}</strong></td>
                    <td>${escapeHtml(p.nom_prenoms)}</td>
                    <td>${escapeHtml(p.telephone)}</td>
                    <td>${escapeHtml(region)}</td>
                    <td>${escapeHtml(sousPref)}</td>
                    <td>${escapeHtml(village)}</td>
                    <td>${escapeHtml(collecteur)}</td>
                    <td>${formatDate(p.created_at)}</td>
                    <td>
                        <a href="planteur_details.php?id=${p.id}" class="btn-action view" title="Voir les détails">
                            <i class="fas fa-eye"></i>
                        </a>
                        ${actionBtn}
                    </td>
                </tr>
            `;
        });
        
        return `
            <div class="doublon-group">
                <div class="doublon-group-header">
                    <h6>
                        <i class="fas fa-user-friends mr-2"></i>
                        Groupe #${index + 1} - ${nom}
                        <small class="ml-2">${tel}</small>
                    </h6>
                    <span class="badge">${groupe.length} entrée(s)</span>
                </div>
                <div class="doublon-group-body">
                    <table class="table table-doublons">
                        <thead>
                            <tr>
                                <th>Statut</th>
                                <th>N° Fiche</th>
                                <th>Nom & Prénoms</th>
                                <th>Téléphone</th>
                                <th>Région</th>
                                <th>Sous-préfecture</th>
                                <th>Village</th>
                                <th>Collecteur</th>
                                <th>Créé le</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rowsHtml}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }
    
    // Charger les doublons depuis l'API
    async function loadDoublons() {
        try {
            const response = await fetch(`${apiUrl}?action=doublons`, { cache: 'no-store' });
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error || 'Erreur lors du chargement des doublons');
            }
            
            const data = result.data;
            
            // Mettre à jour les statistiques
            document.getElementById('statTotalDoublons').textContent = data.total_doublons || 0;
            document.getElementById('statTotalGroupes').textContent = data.total_groupes || 0;
            document.getElementById('statASupprimer').textContent = data.a_supprimer || 0;
            document.getElementById('badgeGroupes').textContent = `${data.total_groupes || 0} groupe(s)`;
            
            // Générer le contenu
            if (!data.groupes || data.groupes.length === 0) {
                doublonsContent.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <h4>Aucun doublon détecté</h4>
                        <p>Tous les planteurs sont uniques dans la base de données.</p>
                    </div>
                `;
            } else {
                let html = '';
                data.groupes.forEach((groupe, index) => {
                    html += renderGroup(groupe, index);
                });
                doublonsContent.innerHTML = html;
            }
            
            // Afficher les conteneurs
            loader.style.display = 'none';
            statsContainer.style.display = 'grid';
            tableContainer.style.display = 'block';
            
        } catch (error) {
            showError(error.message);
        }
    }
    
    // Charger au démarrage
    loadDoublons();
    
    // Exposer la fonction de suppression globalement
    window.confirmDelete = function(id, name) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteName').textContent = name;
        $('#deleteModal').modal('show');
    };
    
    // Gestionnaire de suppression
    document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
        const id = document.getElementById('deleteId').value;
        const btn = this;
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Suppression...';
        
        try {
            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'delete_planteur',
                    id: id
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                $('#deleteModal').modal('hide');
                // Recharger les doublons
                loader.style.display = 'block';
                statsContainer.style.display = 'none';
                tableContainer.style.display = 'none';
                loadDoublons();
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-trash-alt mr-2"></i>Supprimer';
            } else {
                alert('Erreur: ' + (result.error || 'Impossible de supprimer le planteur'));
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-trash-alt mr-2"></i>Supprimer';
            }
        } catch (error) {
            alert('Erreur de connexion: ' + error.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-trash-alt mr-2"></i>Supprimer';
        }
    });
})();
</script>

<?php include('footer.php'); ?>
