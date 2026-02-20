<?php
include('header.php');
?>

<style>
.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 25px 30px;
    border-radius: 12px;
    margin-bottom: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.page-header h2 {
    margin: 0;
    font-weight: 600;
}
.table-container {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-bottom: 25px;
}
.table-container h5 {
    color: #333;
    font-weight: 600;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #667eea;
}
.table thead th {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 12px 15px;
    font-weight: 600;
    font-size: 0.9rem;
}
.table thead th:first-child {
    border-radius: 8px 0 0 0;
}
.table thead th:last-child {
    border-radius: 0 8px 0 0;
}
.table tbody tr {
    transition: background 0.2s;
    cursor: pointer;
}
.table tbody tr:hover {
    background: #f8f9fa;
}
.table tbody td {
    vertical-align: middle;
    padding: 12px 15px;
    border-bottom: 1px solid #eee;
}
.region-badge {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    background: #667eea;
    color: white;
}
.count-badge {
    display: inline-block;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    background: #e8f4fd;
    color: #3498db;
}
.sp-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
    background: #f0f0f0;
    color: #555;
    margin: 2px;
}
.loader-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 200px;
}
.loader-spinner {
    width: 50px;
    height: 50px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
.search-box {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.sp-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.sp-item {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.btn-delete-sp {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    transition: all 0.2s;
    padding: 0;
}
.btn-delete-sp:hover {
    background: #e74c3c;
}
.no-sp {
    color: #999;
    font-style: italic;
}
.stats-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    margin-bottom: 20px;
}
.stats-card h3 {
    margin: 0;
    font-size: 2.5rem;
    font-weight: 700;
}
.stats-card p {
    margin: 5px 0 0;
    opacity: 0.9;
}
</style>

<section class="content">
    <div class="container-fluid">
        <!-- En-tête -->
        <div class="page-header">
            <h2><i class="fas fa-map-marked-alt mr-2"></i>Gestion des Régions</h2>
            <button class="btn btn-light" id="refreshBtn">
                <i class="fas fa-sync-alt mr-1"></i>Actualiser
            </button>
        </div>

        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <h3 id="totalRegions">0</h3>
                    <p><i class="fas fa-map mr-1"></i>Régions</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);">
                    <h3 id="totalSousPrefectures">0</h3>
                    <p><i class="fas fa-building mr-1"></i>Sous-préfectures</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card" style="background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);">
                    <h3 id="avgSousPrefectures">0</h3>
                    <p><i class="fas fa-chart-bar mr-1"></i>Moyenne SP/Région</p>
                </div>
            </div>
        </div>

        <!-- Recherche -->
        <div class="search-box">
            <div class="row">
                <div class="col-md-6">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" id="searchInput" class="form-control" placeholder="Rechercher une région...">
                    </div>
                </div>
            </div>
        </div>


        <!-- Erreur -->
        <div id="errorAlert" class="alert alert-danger" style="display:none;"></div>

        <!-- Loader -->
        <div id="loader" class="loader-container">
            <div class="text-center">
                <div class="loader-spinner mb-3"></div>
                <p class="text-muted">Chargement des régions...</p>
            </div>
        </div>

        <!-- Tableau des régions -->
        <div class="table-container" id="tableContainer" style="display:none;">
            <h5><i class="fas fa-list mr-2"></i>Liste des Régions</h5>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag mr-1"></i>ID</th>
                            <th><i class="fas fa-map mr-1"></i>Nom de la Région</th>
                            <th><i class="fas fa-building mr-1"></i>Sous-préfectures</th>
                            <th><i class="fas fa-eye mr-1"></i>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="regionsTbody"></tbody>
                </table>
            </div>
        </div>

        <!-- Aucun résultat -->
        <div id="noResults" class="text-center py-5" style="display:none;">
            <i class="fas fa-map-marked-alt fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Aucune région trouvée</h5>
        </div>
    </div>
</section>

<!-- Modal Détail Région -->
<div class="modal fade" id="regionModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h5 class="modal-title"><i class="fas fa-map-marker-alt mr-2"></i><span id="modalRegionName">-</span></h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modalRegionId" value="">
                
                <!-- Formulaire d'ajout de sous-préfecture -->
                <div class="add-sp-form mb-4">
                    <label class="font-weight-bold mb-2"><i class="fas fa-plus-circle mr-1"></i>Ajouter une sous-préfecture</label>
                    <div class="input-group">
                        <input type="text" id="newSpName" class="form-control" placeholder="Nom de la nouvelle sous-préfecture...">
                        <div class="input-group-append">
                            <button class="btn btn-success" type="button" id="addSpBtn">
                                <i class="fas fa-plus mr-1"></i>Ajouter
                            </button>
                        </div>
                    </div>
                    <div id="addSpError" class="text-danger small mt-1" style="display:none;"></div>
                    <div id="addSpSuccess" class="text-success small mt-1" style="display:none;"></div>
                </div>
                
                <hr>
                
                <p class="mb-3"><strong><i class="fas fa-building mr-1"></i>Sous-préfectures :</strong> <span id="modalSpCount" class="badge badge-primary">0</span></p>
                
                <!-- Loader pour le modal -->
                <div id="modalLoader" class="text-center py-3" style="display:none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Chargement...</span>
                    </div>
                </div>
                
                <div class="sp-list" id="modalSpList">
                    <!-- Sous-préfectures seront affichées ici -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i>Fermer
                </button>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>

<script>
(function() {
    const apiUrl = '../inc/functions/requete/api_regions.php';
    
    let allRegions = [];
    
    const loaderEl = document.getElementById('loader');
    const tableContainerEl = document.getElementById('tableContainer');
    const tbodyEl = document.getElementById('regionsTbody');
    const errorEl = document.getElementById('errorAlert');
    const noResultsEl = document.getElementById('noResults');
    const searchEl = document.getElementById('searchInput');
    const refreshBtn = document.getElementById('refreshBtn');

    function escapeHtml(v) {
        return String(v ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function renderRow(region) {
        return `
            <tr data-id="${region.id}">
                <td><span class="region-badge">${region.id}</span></td>
                <td><strong>${escapeHtml(region.nom)}</strong></td>
                <td><span class="count-badge">${region.sous_prefectures_count} sous-préfecture(s)</span></td>
                <td>
                    <button type="button" class="btn btn-sm btn-primary view-btn" data-id="${region.id}" title="Voir les sous-préfectures">
                        <i class="fas fa-eye"></i> Voir
                    </button>
                </td>
            </tr>
        `;
    }

    function render(regions) {
        if (!regions.length) {
            tableContainerEl.style.display = 'none';
            noResultsEl.style.display = 'block';
            return;
        }
        noResultsEl.style.display = 'none';
        tableContainerEl.style.display = 'block';
        tbodyEl.innerHTML = regions.map(renderRow).join('');
    }

    function filterRegions() {
        const search = searchEl.value.toLowerCase().trim();
        
        const filtered = allRegions.filter(r => {
            if (search && !r.nom.toLowerCase().includes(search)) return false;
            return true;
        });
        
        render(filtered);
    }

    function updateStats() {
        const totalRegions = allRegions.length;
        let totalSp = 0;
        
        allRegions.forEach(r => {
            totalSp += r.sous_prefectures_count || 0;
        });
        
        const avg = totalRegions > 0 ? (totalSp / totalRegions).toFixed(1) : 0;
        
        document.getElementById('totalRegions').textContent = totalRegions;
        document.getElementById('totalSousPrefectures').textContent = totalSp;
        document.getElementById('avgSousPrefectures').textContent = avg;
    }

    async function loadRegions() {
        loaderEl.style.display = 'flex';
        tableContainerEl.style.display = 'none';
        errorEl.style.display = 'none';
        noResultsEl.style.display = 'none';

        try {
            const res = await fetch(`${apiUrl}?action=list`, { cache: 'no-store' });
            const json = await res.json();

            if (!json.success) {
                throw new Error(json.error || 'Erreur lors du chargement');
            }

            allRegions = json.data || [];
            updateStats();
            render(allRegions);
            tableContainerEl.style.display = 'block';
        } catch (e) {
            errorEl.textContent = e.message;
            errorEl.style.display = 'block';
        } finally {
            loaderEl.style.display = 'none';
        }
    }

    async function showRegionModal(regionId) {
        // Afficher le modal avec loader
        document.getElementById('modalRegionName').textContent = 'Chargement...';
        document.getElementById('modalRegionId').value = regionId;
        document.getElementById('modalSpList').innerHTML = '';
        document.getElementById('modalSpCount').textContent = '0';
        document.getElementById('newSpName').value = '';
        document.getElementById('addSpError').style.display = 'none';
        document.getElementById('addSpSuccess').style.display = 'none';
        document.getElementById('modalLoader').style.display = 'block';
        
        $('#regionModal').modal('show');
        
        try {
            const res = await fetch(`${apiUrl}?action=get&id=${regionId}`, { cache: 'no-store' });
            const json = await res.json();

            if (!json.success) {
                throw new Error(json.error || 'Erreur lors du chargement');
            }

            const region = json.data;
            
            document.getElementById('modalRegionName').textContent = region.nom;
            renderSousPrefectures(region.sous_prefectures);
            
        } catch (e) {
            document.getElementById('modalSpList').innerHTML = `<div class="alert alert-danger">${escapeHtml(e.message)}</div>`;
        } finally {
            document.getElementById('modalLoader').style.display = 'none';
        }
    }

    function renderSousPrefectures(sousPrefectures) {
        const spListEl = document.getElementById('modalSpList');
        if (sousPrefectures.length > 0) {
            spListEl.innerHTML = sousPrefectures
                .map(sp => `
                    <span class="sp-item" data-id="${sp.id}">
                        ${escapeHtml(sp.nom)}
                        <button type="button" class="btn-delete-sp" data-id="${sp.id}" title="Supprimer">
                            <i class="fas fa-times"></i>
                        </button>
                    </span>
                `)
                .join('');
        } else {
            spListEl.innerHTML = '<span class="no-sp">Aucune sous-préfecture enregistrée</span>';
        }
        document.getElementById('modalSpCount').textContent = sousPrefectures.length;
    }

    async function addSousPrefecture() {
        const regionId = document.getElementById('modalRegionId').value;
        const nom = document.getElementById('newSpName').value.trim();
        const addErrorEl = document.getElementById('addSpError');
        const addSuccessEl = document.getElementById('addSpSuccess');
        
        addErrorEl.style.display = 'none';
        addSuccessEl.style.display = 'none';
        
        if (!nom) {
            addErrorEl.textContent = 'Veuillez entrer un nom';
            addErrorEl.style.display = 'block';
            return;
        }
        
        try {
            const res = await fetch(apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'create_sp',
                    region_id: regionId,
                    nom: nom
                })
            });
            
            const json = await res.json();
            
            if (!json.success) {
                throw new Error(json.error || 'Erreur lors de la création');
            }
            
            addSuccessEl.textContent = 'Sous-préfecture ajoutée avec succès !';
            addSuccessEl.style.display = 'block';
            document.getElementById('newSpName').value = '';
            
            // Recharger les détails de la région dans le modal
            showRegionModal(regionId);
            // Recharger la liste des régions pour mettre à jour le compteur
            loadRegions();
            
        } catch (e) {
            addErrorEl.textContent = e.message;
            addErrorEl.style.display = 'block';
        }
    }

    async function deleteSousPrefecture(spId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette sous-préfecture ?')) {
            return;
        }
        
        const regionId = document.getElementById('modalRegionId').value;
        
        try {
            const res = await fetch(apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'delete_sp',
                    id: spId
                })
            });
            
            const json = await res.json();
            
            if (!json.success) {
                throw new Error(json.error || 'Erreur lors de la suppression');
            }
            
            // Recharger les détails de la région dans le modal
            showRegionModal(regionId);
            // Recharger la liste des régions pour mettre à jour le compteur
            loadRegions();
            
        } catch (e) {
            alert('Erreur: ' + e.message);
        }
    }

    // Event listeners
    searchEl.addEventListener('input', filterRegions);
    refreshBtn.addEventListener('click', loadRegions);

    document.getElementById('addSpBtn').addEventListener('click', addSousPrefecture);
    document.getElementById('newSpName').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            addSousPrefecture();
        }
    });

    document.getElementById('modalSpList').addEventListener('click', function(e) {
        const deleteBtn = e.target.closest('.btn-delete-sp');
        if (deleteBtn) {
            const spId = deleteBtn.dataset.id;
            deleteSousPrefecture(spId);
        }
    });

    tbodyEl.addEventListener('click', function(e) {
        const viewBtn = e.target.closest('.view-btn');
        if (viewBtn) {
            const regionId = viewBtn.dataset.id;
            showRegionModal(regionId);
        }
    });

    // Charger les données au démarrage
    loadRegions();
})();
</script>
