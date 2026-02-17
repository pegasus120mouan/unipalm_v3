<?php
require_once '../inc/functions/connexion.php';

include('header.php');
?>

<!-- Leaflet CSS & JS pour la carte -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Message d'erreur/succès -->
<?php if (isset($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?= $_SESSION['error'] ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?= $_SESSION['success'] ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php unset($_SESSION['success']); ?>
<?php endif; ?>

<!-- Reste du code HTML -->

<style>
/* ===== STYLES PROFESSIONNELS POUR TICKETS.PHP ===== */

/* Variables CSS pour cohérence */
:root {
    --primary-color: #2c3e50;
    --secondary-color: #3498db;
    --success-color: #27ae60;
    --warning-color: #f39c12;
    --danger-color: #e74c3c;
    --light-bg: #f8f9fa;
    --border-color: #dee2e6;
    --text-muted: #6c757d;
    --shadow-sm: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    --shadow-md: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    --border-radius: 0.375rem;
    --transition: all 0.3s ease;
}

/* Conteneur principal des actions */
.actions-container {
    background: linear-gradient(135deg, var(--light-bg) 0%, #ffffff 100%);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: var(--shadow-sm);
}

.actions-container .btn {
    margin: 0.25rem;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    border-radius: var(--border-radius);
    transition: var(--transition);
    box-shadow: var(--shadow-sm);
}

.actions-container .btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

/* Amélioration du tableau */
.table-container {
    background: white;
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
    max-height: 70vh;
    overflow-y: auto;
    overflow-x: hidden;
}

#example1 {
    margin-bottom: 0;
    border-collapse: separate;
    border-spacing: 0;
}

#example1 thead th {
    background: linear-gradient(135deg, var(--primary-color) 0%, #34495e 100%);
    color: white;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.875rem;
    letter-spacing: 0.5px;
    padding: 1rem 0.5rem;
    border: none;
    position: sticky;
    top: 0;
    z-index: 10;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

#example1 tbody tr {
    transition: var(--transition);
    border-bottom: 1px solid #f1f3f4;
}

#example1 tbody tr:hover {
    background-color: #f8f9fa;
    transform: scale(1.01);
    box-shadow: var(--shadow-sm);
}

#example1 tbody td {
    padding: 1rem 0.5rem;
    vertical-align: middle;
    border-top: none;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 150px;
}

/* Badges et statuts améliorés */
.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-size: 0.875rem;
    font-weight: 500;
    text-align: center;
    min-width: 120px;
    display: inline-block;
    box-shadow: var(--shadow-sm);
}

.status-pending {
    background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%);
    color: #2d3436;
}

.status-validated {
    background: linear-gradient(135deg, #81ecec 0%, #00cec9 100%);
    color: white;
}

.status-paid {
    background: linear-gradient(135deg, #a29bfe 0%, #6c5ce7 100%);
    color: white;
}

.status-unpaid {
    background: linear-gradient(135deg, #fab1a0 0%, #e17055 100%);
    color: white;
}

/* Boutons d'actions améliorés */
.action-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}

.action-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition);
    box-shadow: var(--shadow-sm);
}

.action-btn:hover {
    transform: scale(1.1);
    box-shadow: var(--shadow-md);
}

.action-btn.edit {
    background: linear-gradient(135deg, var(--secondary-color) 0%, #74b9ff 100%);
    color: white;
}

.action-btn.delete {
    background: linear-gradient(135deg, var(--danger-color) 0%, #fd79a8 100%);
    color: white;
}

.action-btn:disabled {
    background: #95a5a6;
    cursor: not-allowed;
    transform: none;
}

/* Loader amélioré */
#loader {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-sm);
}

.loader-spinner {
    width: 50px;
    height: 50px;
    border: 4px solid var(--light-bg);
    border-top: 4px solid var(--secondary-color);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Pagination professionnelle */
.pagination-container {
    background: white;
    padding: 1.5rem;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
    margin-top: 1.5rem;
}

.pagination-link {
    padding: 0.75rem 1rem;
    margin: 0 0.25rem;
    background: white;
    color: var(--primary-color);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    text-decoration: none;
    transition: var(--transition);
    font-weight: 500;
}

.pagination-link:hover {
    background: var(--secondary-color);
    color: white;
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

/* Formulaires améliorés */
.form-group label {
    font-weight: 600;
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.form-control {
    border: 2px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 0.75rem;
    transition: var(--transition);
    font-size: 1rem;
}

.form-control:focus {
    border-color: var(--secondary-color);
    box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
    outline: none;
}

/* Autocomplete amélioré */
.list {
    background: white;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-md);
    max-height: 250px;
    overflow-y: auto;
    z-index: 1050;
}

.list li {
    padding: 0.75rem 1rem;
    cursor: pointer;
    transition: var(--transition);
    border-bottom: 1px solid #f1f3f4;
}

.list li:hover {
    background: var(--light-bg);
    color: var(--secondary-color);
}

.list li:last-child {
    border-bottom: none;
}

/* Responsive amélioré */
@media (max-width: 768px) {
    .actions-container {
        padding: 1rem;
    }
    
    .actions-container .btn {
        width: 100%;
        margin: 0.25rem 0;
    }
    
    .table-responsive {
        border-radius: var(--border-radius);
        overflow: hidden;
    }
    
    #example1 thead {
        display: none;
    }
    
    #example1 tbody tr {
        display: block;
        margin-bottom: 1rem;
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-sm);
        padding: 1rem;
    }
    
    #example1 tbody td {
        display: block;
        text-align: left !important;
        padding: 0.5rem 0;
        border: none;
    }
    
    #example1 tbody td:before {
        content: attr(data-label) ": ";
        font-weight: 600;
        color: var(--primary-color);
        display: inline-block;
        width: 120px;
    }
}

/* Modales améliorées */
.modal-header {
    background: linear-gradient(135deg, var(--primary-color) 0%, #34495e 100%);
    color: white;
    border-radius: var(--border-radius) var(--border-radius) 0 0;
}

.modal-content {
    border: none;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-md);
}

.modal-footer .btn {
    border-radius: var(--border-radius);
    padding: 0.75rem 1.5rem;
    font-weight: 500;
}

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in-up {
    animation: fadeInUp 0.5s ease-out;
}

/* Utilitaires */
.text-gradient {
    background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.card-hover {
    transition: var(--transition);
}

.card-hover:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-md);
}

/* Bouton Localisation stylisé */
.localisation-btn {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 10px 20px;
    font-weight: 600;
    font-size: 14px;
    box-shadow: 0 4px 15px rgba(17, 153, 142, 0.3);
    transition: all 0.3s ease;
    text-decoration: none;
}

.localisation-btn:hover {
    background: linear-gradient(135deg, #0f8a7e 0%, #2ed573 100%);
    color: white;
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(17, 153, 142, 0.4);
    text-decoration: none;
}

.localisation-btn i {
    margin-right: 8px;
}

/* Carte de filtres avancés */
.filter-card {
    background: white;
    border-radius: 15px;
    padding: 25px 30px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
    border-left: 4px solid #667eea;
    margin-bottom: 20px;
}

.filter-title {
    color: #2c3e50;
    font-weight: 700;
    font-size: 1.2rem;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
}

.filter-title i {
    color: #667eea;
}

.filter-label {
    font-weight: 600;
    color: #2c3e50;
    font-size: 0.9rem;
    margin-bottom: 8px;
    display: block;
}

.filter-label i {
    color: #667eea;
}

.filter-input {
    border: 1px solid #e0e6ed;
    border-radius: 8px;
    padding: 12px 15px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.filter-input:focus {
    border-color: #667eea;
    background: white;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
}

.filter-input::placeholder {
    color: #adb5bd;
}

.btn-search {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 25px;
    padding: 12px 30px;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    margin: 0 5px;
}

.btn-search:hover {
    background: linear-gradient(135deg, #5a6fd6 0%, #6a4190 100%);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn-reset {
    background: #6c757d;
    color: white;
    border: none;
    border-radius: 25px;
    padding: 12px 30px;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    margin: 0 5px;
}

.btn-reset:hover {
    background: #5a6268;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(108, 117, 125, 0.4);
}
</style>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Liste des planteurs</h1>
            </div>
        </div>
    </div>
</section>

<div class="row">
    <!-- Filtres de recherche avancés -->
    <div class="col-12 mb-4">
        <div class="filter-card fade-in-up">
            <h5 class="filter-title">
                <i class="fas fa-filter mr-2"></i>Filtres de Recherche
              </h5>
              <div class="row">
                <div class="col-md-4 mb-3">
                  <label class="filter-label"><i class="fas fa-user mr-1"></i>Nom/Prénom</label>
                  <input type="text" id="filterNom" class="form-control filter-input" placeholder="Rechercher par nom ou prénom...">
                </div>
                <div class="col-md-4 mb-3">
                  <label class="filter-label"><i class="fas fa-phone mr-1"></i>Téléphone</label>
                  <input type="text" id="filterTelephone" class="form-control filter-input" placeholder="Rechercher par téléphone...">
                </div>
                <div class="col-md-4 mb-3">
                  <label class="filter-label"><i class="fas fa-users mr-1"></i>Collecteur</label>
                  <input type="text" id="filterCollecteur" class="form-control filter-input" placeholder="Rechercher par collecteur...">
                </div>
              </div>
              <div class="text-center mt-2">
                <button id="planteursRefresh" type="button" class="btn btn-search">
                  <i class="fas fa-search mr-2"></i>Rechercher
                </button>
                <button id="planteursReset" type="button" class="btn btn-reset">
                  <i class="fas fa-times mr-2"></i>Réinitialiser
                </button>
              </div>
            </div>
          </div>

          <div id="planteursError" class="alert alert-danger" style="display:none;"></div>

          <div class="table-container fade-in-up">
            <div id="loader" class="text-center">
              <div class="loader-spinner"></div>
              <h5 class="text-muted">Chargement des planteurs...</h5>
            </div>

            <div class="d-flex justify-content-end mb-3">
              <a href="localisation.php" class="btn localisation-btn">
                <i class="fas fa-map-marked-alt mr-2"></i>Localisation des plantations
              </a>
            </div>
            <div class="table-responsive" style="overflow-x: hidden;">
              <table id="example1" class="table table-hover" style="display: none; width: 100%; table-layout: fixed;">
                <thead>
                  <tr>
                    <th>Photo du planteur</th>  
                    <th>Numéro fiche</th>
                    <th>Nom & prénoms</th>
                    <th>Téléphone</th>
                    <th>Collecteur</th>
                    <th>Région</th>
                    <th>Village</th>
                    <th>Créé le</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="planteursTbody"></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<div class="modal fade" id="parcellesMapModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Cartographie des parcelles</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="parcellesMapHint" class="alert alert-info" style="margin-bottom: 10px; display:none;"></div>
        <div id="parcellesMap" style="height: 70vh; width: 100%; background:#ffffff;"></div>
      </div>
    </div>
  </div>
</div>

<script>
  (function () {
    const apiBaseUrl = '../inc/functions/requete/api_requete_planteurs.php';
    const minioBaseUrl = <?php echo json_encode(getenv('AWS_URL') ?: 'http://51.178.49.141:9000/planteurs'); ?>;
    const errorEl = document.getElementById('planteursError');
    const loaderEl = document.getElementById('loader');
    const tableEl = document.getElementById('example1');
    const tbodyEl = document.getElementById('planteursTbody');
    const searchEl = document.getElementById('planteursSearch');
    const refreshEl = document.getElementById('planteursRefresh');

    const defaultPhotoSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80">
  <rect width="80" height="80" rx="40" fill="#E9ECEF"/>
  <circle cx="40" cy="32" r="14" fill="#ADB5BD"/>
  <path d="M16 70c4-14 18-22 24-22s20 8 24 22" fill="#ADB5BD"/>
</svg>`;
    const defaultPhoto = `data:image/svg+xml;utf8,${encodeURIComponent(defaultPhotoSvg)}`;

    let allRows = [];

    function buildApiUrl(params) {
      const qs = new URLSearchParams(params || {}).toString();
      return qs ? `${apiBaseUrl}?${qs}` : apiBaseUrl;
    }

    function escapeHtml(v) {
      return String(v ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
    }

    function fmtDate(v) {
      if (!v) return '';
      const d = new Date(v);
      if (Number.isNaN(d.getTime())) return v;
      const dd = String(d.getDate()).padStart(2, '0');
      const mm = String(d.getMonth() + 1).padStart(2, '0');
      const yy = d.getFullYear();
      return `${dd}/${mm}/${yy}`;
    }

    function encodePath(path) {
      return String(path)
        .split('/')
        .map((seg) => encodeURIComponent(seg))
        .join('/');
    }

    function getPhotoValue(p) {
      return (
        p?.photo_url ||
        p?.image_url ||
        p?.photo ||
        p?.photo_planteur ||
        p?.image ||
        p?.image_planteur ||
        p?.avatar ||
        p?.profil_photo ||
        ''
      );
    }

    const isHttps = window.location.protocol === 'https:';

    function buildPhotoUrl(value) {
      const v = String(value || '').trim();
      if (!v) return '';
      let url;
      if (/^https?:\/\//i.test(v)) {
        url = v;
      } else {
        url = `${String(minioBaseUrl).replace(/\/$/, '')}/${encodePath(v)}`;
      }
      if (isHttps && url.startsWith('http://')) {
        return '../inc/functions/requete/proxy_image.php?url=' + encodeURIComponent(url);
      }
      return url;
    }

    function render(rows) {
      tbodyEl.innerHTML = rows
        .map((p, idx) => {
          const collecteur = p.collecteur
            ? `${p.collecteur.nom ?? ''} ${p.collecteur.prenoms ?? ''}`.trim()
            : '';
          const region = p.exploitation?.region ?? '';
          const village = p.exploitation?.sous_prefecture_village ?? '';
          const lat = p.exploitation?.latitude;
          const lng = p.exploitation?.longitude;
          const hasCoords = lat !== null && lat !== undefined && lat !== '' && lng !== null && lng !== undefined && lng !== '';
          const photoUrl = buildPhotoUrl(getPhotoValue(p));
          const photoSrc = photoUrl || defaultPhoto;

          return `
            <tr>
              <td>
                <img
                  src="${escapeHtml(photoSrc)}"
                  alt="Photo"
                  style="width:60px;height:60px;object-fit:cover;border-radius:50%;"
                  onerror="this.onerror=null;this.src='${escapeHtml(defaultPhoto)}';"
                />
              </td>
              <td>${escapeHtml(p.numero_fiche)}</td>
              <td>${escapeHtml(p.nom_prenoms)}</td>
              <td>${escapeHtml(p.telephone)}</td>
              <td>${escapeHtml(collecteur)}</td>
              <td>${escapeHtml(region)}</td>
              <td>${escapeHtml(village)}</td>
              <td>${escapeHtml(fmtDate(p.created_at))}</td>
              <td>
                <div class="action-buttons">
                  <button type="button" class="action-btn edit" data-action="view" data-id="${escapeHtml(p.id)}" title="Voir">
                    <i class="fas fa-eye"></i>
                  </button>
                  <button type="button" class="action-btn edit" data-action="map" data-id="${escapeHtml(p.id)}" title="Voir sur la carte">
                    <i class="fas fa-map-marker-alt"></i>
                  </button>
                  <button type="button" class="action-btn edit" data-action="edit" data-id="${escapeHtml(p.id)}" title="Modifier">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button type="button" class="action-btn delete" data-action="delete" data-id="${escapeHtml(p.id)}" title="Supprimer">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          `;
        })
        .join('');
    }

    let pendingMapPlanteur = null;

    function drawParcelles(planteur) {
      const hintEl = document.getElementById('parcellesMapHint');
      if (hintEl) {
        hintEl.style.display = 'none';
        hintEl.textContent = '';
      }

      const mapDiv = document.getElementById('parcellesMap');
      if (!mapDiv) return;
      mapDiv.innerHTML = '';

      function pickNumber(obj, keys) {
        if (!obj || typeof obj !== 'object') return NaN;
        for (const k of keys) {
          if (obj[k] !== undefined && obj[k] !== null && obj[k] !== '') {
            const n = Number(obj[k]);
            if (Number.isFinite(n)) return n;
          }
        }
        return NaN;
      }

      const cultures = Array.isArray(planteur?.cultures) ? planteur.cultures : [];
      const parcellesFromCultures = cultures.flatMap((c) => {
        const p = c?.parcelles;
        if (Array.isArray(p)) return p;
        if (p && typeof p === 'object') return Object.values(p);
        return [];
      });

      const parcelles = planteur?.parcelles;
      const exploitationParcelles = planteur?.exploitation?.parcelles;

      const parcellesList = parcellesFromCultures.length
        ? parcellesFromCultures
        : Array.isArray(parcelles)
          ? parcelles
          : parcelles && typeof parcelles === 'object'
            ? Object.values(parcelles)
            : Array.isArray(exploitationParcelles)
              ? exploitationParcelles
              : exploitationParcelles && typeof exploitationParcelles === 'object'
                ? Object.values(exploitationParcelles)
                : Array.isArray(planteur?.exploitation?.points)
                  ? [{ points: planteur.exploitation.points }]
                  : [];

      const boundsPoints = [];
      let totalPoints = 0;
      const paths = [];

      for (const parcelle of parcellesList) {
        const rawPoints = parcelle?.points;
        let normalized = rawPoints;
        if (typeof normalized === 'string') {
          try {
            normalized = JSON.parse(normalized);
          } catch (e) {
            normalized = null;
          }
        }

        const points = Array.isArray(normalized)
          ? normalized
          : normalized && typeof normalized === 'object'
            ? Object.values(normalized)
            : [];
        const latlngs = points
          .map((pt) => {
            if (Array.isArray(pt) && pt.length >= 2) {
              const la = Number(pt[0]);
              const lo = Number(pt[1]);
              if (Number.isFinite(la) && Number.isFinite(lo)) {
                boundsPoints.push([la, lo]);
                return [la, lo];
              }
            }

            const la = pickNumber(pt, ['latitude', 'Latitude', 'lat', 'Lat']);
            const lo = pickNumber(pt, ['longitude', 'Longitude', 'lng', 'Lng', 'lon', 'Lon']);
            if (!Number.isFinite(la) || !Number.isFinite(lo)) return null;
            boundsPoints.push([la, lo]);
            return [la, lo];
          })
          .filter(Boolean);

        for (const ll of latlngs) {
          totalPoints += 1;
        }

        if (latlngs.length >= 2) {
          paths.push(latlngs);
        }
      }

      if (hintEl) {
        if (totalPoints > 0) {
          hintEl.textContent = `ID: ${planteur?.id ?? ''} | Parcelles: ${parcellesList.length} | Points tracés: ${totalPoints}`;
        } else {
          const sample = parcellesList?.[0]?.points?.[0] || (parcellesList?.[0]?.points && typeof parcellesList?.[0]?.points === 'object' ? Object.values(parcellesList?.[0]?.points)[0] : null);
          const keys = sample && typeof sample === 'object' ? Object.keys(sample).slice(0, 6).join(', ') : '';
          hintEl.textContent = keys
            ? `ID: ${planteur?.id ?? ''} | Aucun point détecté (parcelles=${parcellesList.length}). Clés exemple: ${keys}`
            : `ID: ${planteur?.id ?? ''} | Aucun point détecté (parcelles=${parcellesList.length}).`;
        }
        hintEl.style.display = 'block';
      }

      if (!boundsPoints.length) {
        return;
      }

      // Détruire la carte précédente si elle existe
      if (window.parcellesLeafletMap) {
        window.parcellesLeafletMap.remove();
        window.parcellesLeafletMap = null;
      }

      // Créer la carte Leaflet
      const map = L.map(mapDiv, {
        zoomControl: true,
        scrollWheelZoom: true
      });
      window.parcellesLeafletMap = map;

      // Ajouter le fond de carte OpenStreetMap
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19
      }).addTo(map);

      // Ajouter les polylignes (parcelles)
      const polylineStyle = {
        color: '#1f6feb',
        weight: 3,
        opacity: 0.8,
        fillColor: '#3498db',
        fillOpacity: 0.2
      };

      paths.forEach((latlngs) => {
        if (latlngs.length >= 3) {
          // Polygone fermé
          L.polygon(latlngs, polylineStyle).addTo(map);
        } else if (latlngs.length >= 2) {
          // Polyligne
          L.polyline(latlngs, polylineStyle).addTo(map);
        }
      });

      // Ajouter les marqueurs pour chaque point
      const pointIcon = L.divIcon({
        className: 'custom-point-marker',
        html: '<div style="width:10px;height:10px;background:#e74c3c;border-radius:50%;border:2px solid white;box-shadow:0 2px 4px rgba(0,0,0,0.3);"></div>',
        iconSize: [10, 10],
        iconAnchor: [5, 5]
      });

      boundsPoints.forEach((p, idx) => {
        L.marker(p, { icon: pointIcon })
          .bindPopup(`Point ${idx + 1}<br>Lat: ${p[0].toFixed(6)}<br>Lng: ${p[1].toFixed(6)}`)
          .addTo(map);
      });

      // Ajuster la vue pour montrer tous les points
      const bounds = L.latLngBounds(boundsPoints);
      map.fitBounds(bounds, { padding: [30, 30] });
    }

    $('#parcellesMapModal').on('shown.bs.modal', function () {
      if (pendingMapPlanteur) {
        drawParcelles(pendingMapPlanteur);
        pendingMapPlanteur = null;
      }
    });

    async function fetchPlanteurDetails(id) {
      const res = await fetch(buildApiUrl({ action: 'planteurs', id: String(id) }), { cache: 'no-store' });
      const json = await res.json();
      if (!res.ok || !json?.success) {
        throw new Error(json?.error || json?.message || 'Erreur API');
      }

      const planteur = json?.data?.planteurs?.[0] || json?.data;

      if (!planteur || !planteur.id) {
        throw new Error('Planteur introuvable.');
      }
      return planteur;
    }

    tbodyEl.addEventListener('click', async (e) => {
      const btn = e.target?.closest?.('button[data-action]');
      if (!btn) return;

      const action = btn.getAttribute('data-action');

      if (action === 'map') {
        const id = btn.getAttribute('data-id');
        if (!id) return;
        try {
          $('#parcellesMapModal').modal('show');

          const hintEl = document.getElementById('parcellesMapHint');
          if (hintEl) {
            hintEl.textContent = 'Chargement de la cartographie...';
            hintEl.style.display = 'block';
          }
          const mapDiv = document.getElementById('parcellesMap');
          if (mapDiv) {
            mapDiv.innerHTML = '';
          }

          const planteur = await fetchPlanteurDetails(id);
          const modalEl = document.getElementById('parcellesMapModal');
          if (modalEl && modalEl.classList.contains('show')) {
            setTimeout(() => {
              drawParcelles(planteur);
            }, 0);
            pendingMapPlanteur = null;
          } else {
            pendingMapPlanteur = planteur;
          }
        } catch (e) {
          alert(e?.message || String(e));
        }

        return;
      }

      const id = btn.getAttribute('data-id');
      if (!id) return;

      if (action === 'view') {
        window.location.href = `planteur_details.php?id=${encodeURIComponent(id)}`;
        return;
      }

      if (action === 'edit') {
        window.location.href = `planteur_edit.php?id=${encodeURIComponent(id)}`;
        return;
      }

      if (action === 'delete') {
        if (!confirm('Supprimer ce planteur ?')) return;
        alert(`Suppression planteur ID: ${id}`);
      }
    });

    function applyFilter() {
      const filterNom = (document.getElementById('filterNom')?.value || '').toLowerCase().trim();
      const filterTel = (document.getElementById('filterTelephone')?.value || '').toLowerCase().trim();
      const filterCollecteur = (document.getElementById('filterCollecteur')?.value || '').toLowerCase().trim();

      if (!filterNom && !filterTel && !filterCollecteur) {
        render(allRows);
        return;
      }

      const filtered = allRows.filter((p) => {
        const collecteur = p.collecteur
          ? `${p.collecteur.nom ?? ''} ${p.collecteur.prenoms ?? ''}`.trim().toLowerCase()
          : '';
        const nom = (p.nom_prenoms || '').toLowerCase();
        const tel = (p.telephone || '').toLowerCase();

        let match = true;
        if (filterNom && !nom.includes(filterNom)) match = false;
        if (filterTel && !tel.includes(filterTel)) match = false;
        if (filterCollecteur && !collecteur.includes(filterCollecteur)) match = false;

        return match;
      });

      render(filtered);
    }

    // Bouton Réinitialiser
    const resetBtn = document.getElementById('planteursReset');
    if (resetBtn) {
      resetBtn.addEventListener('click', function() {
        document.getElementById('filterNom').value = '';
        document.getElementById('filterTelephone').value = '';
        document.getElementById('filterCollecteur').value = '';
        render(allRows);
      });
    }

    async function load() {
      errorEl.style.display = 'none';
      loaderEl.style.display = 'block';
      tableEl.style.display = 'none';
      tbodyEl.innerHTML = '';

      try {
        const res = await fetch(buildApiUrl({ action: 'planteurs' }), { cache: 'no-store' });
        const json = await res.json();
        if (!res.ok || !json?.success) {
          throw new Error(json?.error || json?.message || 'Erreur API');
        }

        allRows = json.data?.planteurs || [];
        render(allRows);
        loaderEl.style.display = 'none';
        tableEl.style.display = 'table';
      } catch (e) {
        errorEl.textContent = e?.message || String(e);
        errorEl.style.display = 'block';
      } finally {
        loaderEl.style.display = 'none';
      }
    }


    refreshEl.addEventListener('click', function() {
      applyFilter();
    });
    load();
  })();
</script>

<?php include('footer.php'); ?>