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
}
.table tbody tr:hover {
    background: #f8f9fa;
}
.table tbody td {
    vertical-align: middle;
    padding: 12px 15px;
    border-bottom: 1px solid #eee;
}
.action-btn {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    border: none;
    margin: 0 2px;
    cursor: pointer;
    transition: transform 0.2s;
}
.action-btn:hover {
    transform: scale(1.1);
}
.action-btn.edit { background: #3498db; color: white; }
.action-btn.delete { background: #e74c3c; color: white; }
.action-btn.assign { background: #2ecc71; color: white; }
.btn-add-zone {
    background: white;
    color: #667eea;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s;
}
.btn-add-zone:hover {
    background: #f8f9fa;
    transform: translateY(-2px);
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
.zone-badge {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    background: #667eea;
    color: white;
}
.no-zone-badge {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    background: #95a5a6;
    color: white;
}
.collecteur-count {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 15px;
    font-size: 0.85rem;
    font-weight: 600;
    background: #e8f4fd;
    color: #3498db;
}
.modal-header-custom {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px 12px 0 0;
}
.modal-header-custom .close {
    color: white;
    opacity: 1;
}
.modal-content {
    border-radius: 12px;
    border: none;
}
</style>

<section class="content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="page-header">
            <h2><i class="fas fa-map-marked-alt mr-2"></i>Gestion des Zones</h2>
            <button class="btn-add-zone" onclick="openAddZoneModal()">
                <i class="fas fa-plus mr-2"></i>Nouvelle Zone
            </button>
        </div>

        <!-- Liste des zones -->
        <div class="table-container">
            <h5><i class="fas fa-layer-group mr-2"></i>Liste des Zones</h5>
            <div id="zonesLoader" class="loader-container">
                <div class="loader-spinner"></div>
            </div>
            <div id="zonesTableContainer" style="display:none;">
                <table class="table" id="zonesTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nom de la Zone</th>
                            <th>Collecteurs assignés</th>
                            <th>Date de création</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="zonesTableBody">
                    </tbody>
                </table>
            </div>
            <div id="zonesEmpty" class="text-center py-4" style="display:none;">
                <i class="fas fa-map-marker-alt fa-3x text-muted mb-3"></i>
                <p class="text-muted">Aucune zone enregistrée</p>
            </div>
        </div>

    </div>
</section>

<!-- Modal Ajouter/Modifier Zone -->
<div class="modal fade" id="zoneModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title" id="zoneModalTitle">
                    <i class="fas fa-plus-circle mr-2"></i>Nouvelle Zone
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="zoneForm">
                    <input type="hidden" id="zoneId" name="id">
                    <div class="form-group">
                        <label><i class="fas fa-tag mr-1"></i>Nom de la zone <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nomZone" name="nom_zone" required placeholder="Ex: Zone Nord">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="saveZone()">
                    <i class="fas fa-save mr-1"></i>Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>


<!-- Modal Confirmation Suppression -->
<div class="modal fade" id="deleteZoneModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Confirmer la suppression
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer la zone <strong id="deleteZoneName"></strong> ?</p>
                <p class="text-muted small">Les collecteurs assignés à cette zone seront désassignés.</p>
            </div>
            <div class="modal-footer">
                <input type="hidden" id="deleteZoneId">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" onclick="confirmDeleteZone()">
                    <i class="fas fa-trash mr-1"></i>Supprimer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const apiZones = '../inc/functions/requete/api_zones.php';

    let zones = [];

    async function loadZones() {
        try {
            const res = await fetch(`${apiZones}?action=list`, { cache: 'no-store' });
            const json = await res.json();
            
            document.getElementById('zonesLoader').style.display = 'none';
            
            if (json.success && json.data && json.data.length > 0) {
                zones = json.data;
                renderZonesTable(zones);
                document.getElementById('zonesTableContainer').style.display = 'block';
                updateZoneSelect();
            } else {
                zones = [];
                document.getElementById('zonesEmpty').style.display = 'block';
            }
        } catch (e) {
            console.error('Erreur chargement zones:', e);
            document.getElementById('zonesLoader').style.display = 'none';
            document.getElementById('zonesEmpty').style.display = 'block';
        }
    }

    function renderZonesTable(zones) {
        const tbody = document.getElementById('zonesTableBody');
        tbody.innerHTML = zones.map((z, i) => `
            <tr>
                <td>${i + 1}</td>
                <td><strong>${escapeHtml(z.nom_zone)}</strong></td>
                <td><span class="collecteur-count">${z.collecteurs_count || 0} collecteur(s)</span></td>
                <td>${formatDate(z.created_at)}</td>
                <td>
                    <button class="action-btn edit" title="Modifier" onclick="openEditZoneModal(${z.id}, '${escapeHtml(z.nom_zone)}')">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="action-btn delete" title="Supprimer" onclick="openDeleteZoneModal(${z.id}, '${escapeHtml(z.nom_zone)}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function formatDate(dateStr) {
        if (!dateStr) return '-';
        const d = new Date(dateStr);
        return d.toLocaleDateString('fr-FR');
    }

    window.openAddZoneModal = function() {
        document.getElementById('zoneModalTitle').innerHTML = '<i class="fas fa-plus-circle mr-2"></i>Nouvelle Zone';
        document.getElementById('zoneId').value = '';
        document.getElementById('nomZone').value = '';
        $('#zoneModal').modal('show');
    };

    window.openEditZoneModal = function(id, nom) {
        document.getElementById('zoneModalTitle').innerHTML = '<i class="fas fa-edit mr-2"></i>Modifier la Zone';
        document.getElementById('zoneId').value = id;
        document.getElementById('nomZone').value = nom;
        $('#zoneModal').modal('show');
    };

    window.openDeleteZoneModal = function(id, nom) {
        document.getElementById('deleteZoneId').value = id;
        document.getElementById('deleteZoneName').textContent = nom;
        $('#deleteZoneModal').modal('show');
    };

    window.saveZone = async function() {
        const id = document.getElementById('zoneId').value;
        const nom = document.getElementById('nomZone').value.trim();

        if (!nom) {
            alert('Veuillez saisir un nom de zone');
            return;
        }

        try {
            const res = await fetch(apiZones, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: id ? 'update' : 'create',
                    id: id || undefined,
                    nom_zone: nom
                })
            });

            const json = await res.json();

            if (json.success) {
                $('#zoneModal').modal('hide');
                loadZones();
            } else {
                alert(json.error || 'Erreur lors de l\'enregistrement');
            }
        } catch (e) {
            alert('Erreur: ' + e.message);
        }
    };

    window.confirmDeleteZone = async function() {
        const id = document.getElementById('deleteZoneId').value;

        try {
            const res = await fetch(apiZones, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'delete',
                    id: id
                })
            });

            const json = await res.json();

            if (json.success) {
                $('#deleteZoneModal').modal('hide');
                loadZones();
            } else {
                alert(json.error || 'Erreur lors de la suppression');
            }
        } catch (e) {
            alert('Erreur: ' + e.message);
        }
    };

    // Initialisation
    loadZones();
})();
</script>

<?php include('footer.php'); ?>
