<?php
include('header.php');

$id = isset($_GET['id']) ? trim((string)$_GET['id']) : '';
?>

  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-8">
          <h1>Détails du planteur</h1>
        </div>
        <div class="col-sm-4 text-right">
          <a href="plantations.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Retour
          </a>
        </div>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div id="planteurError" class="alert alert-danger" style="display:none;"></div>

      <div id="planteurLoader" class="text-center" style="padding: 30px;">
        <div class="spinner-border text-primary" role="status">
          <span class="sr-only">Chargement...</span>
        </div>
        <div class="mt-2 text-muted">Chargement des informations...</div>
      </div>

      <div id="planteurContent" style="display:none;">
        <div class="row">
          <div class="col-lg-4">
            <div class="card">
              <div class="card-body text-center">
                <img id="planteurPhoto" src="" alt="Photo" style="width:140px;height:140px;object-fit:cover;border-radius:50%;" />
                <h4 id="planteurNom" class="mt-3 mb-1"></h4>
                <div id="planteurFiche" class="text-muted"></div>
              </div>
            </div>

            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Contact</h3>
              </div>
              <div class="card-body">
                <div><strong>Téléphone:</strong> <span id="planteurTel"></span></div>
                <div class="mt-2"><strong>Pièce:</strong> <span id="planteurPiece"></span></div>
                <div class="mt-2"><strong>Situation:</strong> <span id="planteurSituation"></span></div>
                <div class="mt-2"><strong>Enfants:</strong> <span id="planteurEnfants"></span></div>
              </div>
            </div>

            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Collecteur</h3>
              </div>
              <div class="card-body">
                <div id="planteurCollecteur"></div>
              </div>
            </div>
          </div>

          <div class="col-lg-8">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Identité</h3>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6"><strong>Date naissance:</strong> <span id="planteurDateN"></span></div>
                  <div class="col-md-6"><strong>Lieu naissance:</strong> <span id="planteurLieuN"></span></div>
                </div>
                <div class="row mt-2">
                  <div class="col-md-6"><strong>Date enregistrement:</strong> <span id="planteurDateEnreg"></span></div>
                  <div class="col-md-6"><strong>Créé le:</strong> <span id="planteurCreatedAt"></span></div>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Exploitation</h3>
                <button id="openParcellesMap" type="button" class="btn btn-info btn-sm" style="display:none;">
                  <i class="fas fa-map-marker-alt mr-2"></i>Cartographie
                </button>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6"><strong>Région:</strong> <span id="explRegion"></span></div>
                  <div class="col-md-6"><strong>Village:</strong> <span id="explVillage"></span></div>
                </div>
                <div class="row mt-2">
                  <div class="col-md-6"><strong>Latitude:</strong> <span id="explLat"></span></div>
                  <div class="col-md-6"><strong>Longitude:</strong> <span id="explLng"></span></div>
                </div>
                <div class="row mt-3">
                  <div class="col-12">
                    <div id="videoWrap" style="display:none;">
                      <strong>Vidéo:</strong>
                      <video id="explVideo" controls style="width: 100%; max-height: 420px; margin-top: 8px;" preload="metadata"></video>
                      <div class="mt-2">
                        <a id="explVideoLink" href="#" target="_blank" rel="noopener" style="display:none;">Ouvrir la vidéo</a>
                        <a id="explVideoDownload" href="#" download class="btn btn-success btn-sm ml-2" style="display:none;">
                          <i class="fas fa-download mr-1"></i>Télécharger la vidéo
                        </a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Cultures</h3>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table table-bordered">
                    <thead>
                      <tr>
                        <th>Type</th>
                        <th>Superficie (ha)</th>
                        <th>Âge</th>
                        <th>Mode</th>
                        <th>Production estimée (kg)</th>
                      </tr>
                    </thead>
                    <tbody id="culturesTbody"></tbody>
                  </table>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Informations complémentaires</h3>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6"><strong>Semences:</strong> <span id="infoSemences"></span></div>
                  <div class="col-md-6"><strong>Phytosanitaires:</strong> <span id="infoPhyto"></span></div>
                </div>
                <div class="row mt-2">
                  <div class="col-md-6"><strong>Travailleurs:</strong> <span id="infoTrav"></span></div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>

      <div class="modal fade" id="parcellesMapModalDetails" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Cartographie des parcelles</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <div id="parcellesMapDetails" style="height: 70vh; width: 100%;"></div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script>
  (function () {
    const planteurId = <?php echo json_encode($id); ?>;
    const apiUrl = '../inc/functions/requete/api_requete_planteurs.php';

    const errorEl = document.getElementById('planteurError');
    const loaderEl = document.getElementById('planteurLoader');
    const contentEl = document.getElementById('planteurContent');

    const defaultPhotoSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="140" height="140" viewBox="0 0 80 80">
  <rect width="80" height="80" rx="40" fill="#E9ECEF"/>
  <circle cx="40" cy="32" r="14" fill="#ADB5BD"/>
  <path d="M16 70c4-14 18-22 24-22s20 8 24 22" fill="#ADB5BD"/>
</svg>`;
    const defaultPhoto = `data:image/svg+xml;utf8,${encodeURIComponent(defaultPhotoSvg)}`;

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

    let parcellesMap = null;
    let parcellesLayer = null;

    function ensureMap() {
      if (parcellesMap) return;
      parcellesMap = L.map('parcellesMapDetails');
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap',
      }).addTo(parcellesMap);
      parcellesLayer = L.featureGroup().addTo(parcellesMap);
    }

    function clearMap() {
      if (parcellesLayer) parcellesLayer.clearLayers();
    }

    function drawParcelles(planteur) {
      clearMap();

      const parcelles = Array.isArray(planteur?.parcelles) ? planteur.parcelles : [];
      const shapes = [];
      const boundsPoints = [];

      for (const parcelle of parcelles) {
        const points = Array.isArray(parcelle?.points) ? parcelle.points : [];
        const latlngs = points
          .map((pt) => {
            const la = Number(pt?.latitude);
            const lo = Number(pt?.longitude);
            if (!Number.isFinite(la) || !Number.isFinite(lo)) return null;
            boundsPoints.push([la, lo]);
            return [la, lo];
          })
          .filter(Boolean);

        if (latlngs.length >= 3) {
          shapes.push(L.polygon(latlngs, { color: '#3498db', weight: 2, fillOpacity: 0.15 }));
        } else if (latlngs.length >= 2) {
          shapes.push(L.polyline(latlngs, { color: '#3498db', weight: 2 }));
        } else if (latlngs.length === 1) {
          shapes.push(L.marker(latlngs[0]));
        }
      }

      if (!shapes.length) {
        const la = Number(planteur?.exploitation?.latitude);
        const lo = Number(planteur?.exploitation?.longitude);
        if (Number.isFinite(la) && Number.isFinite(lo)) {
          shapes.push(L.marker([la, lo]));
          boundsPoints.push([la, lo]);
        }
      }

      for (const s of shapes) s.addTo(parcellesLayer);

      if (boundsPoints.length) {
        parcellesMap.fitBounds(boundsPoints, { padding: [30, 30] });
      } else {
        parcellesMap.setView([5.35, -4.0], 7);
      }
    }

    $('#parcellesMapModalDetails').on('shown.bs.modal', function () {
      ensureMap();
      setTimeout(() => {
        parcellesMap.invalidateSize();
      }, 0);
    });

    function fill(planteur) {
      document.getElementById('planteurPhoto').src = planteur?.photo_url || defaultPhoto;
      document.getElementById('planteurPhoto').onerror = function () {
        this.onerror = null;
        this.src = defaultPhoto;
      };

      document.getElementById('planteurNom').textContent = planteur?.nom_prenoms || '';
      document.getElementById('planteurFiche').textContent = planteur?.numero_fiche ? `N° fiche: ${planteur.numero_fiche}` : '';

      document.getElementById('planteurTel').textContent = planteur?.telephone || '';
      document.getElementById('planteurPiece').textContent = planteur?.piece_identite || '';
      document.getElementById('planteurSituation').textContent = planteur?.situation_matrimoniale || '';
      document.getElementById('planteurEnfants').textContent = planteur?.nombre_enfants ?? '';

      const collecteur = planteur?.collecteur ? `${planteur.collecteur.nom ?? ''} ${planteur.collecteur.prenoms ?? ''}`.trim() : '';
      document.getElementById('planteurCollecteur').textContent = collecteur;

      document.getElementById('planteurDateN').textContent = fmtDate(planteur?.date_naissance);
      document.getElementById('planteurLieuN').textContent = planteur?.lieu_naissance || '';
      document.getElementById('planteurDateEnreg').textContent = fmtDate(planteur?.date_enregistrement);
      document.getElementById('planteurCreatedAt').textContent = planteur?.created_at ? fmtDate(planteur.created_at) : '';

      const expl = planteur?.exploitation || {};
      document.getElementById('explRegion').textContent = expl?.region || '';
      document.getElementById('explVillage').textContent = expl?.sous_prefecture_village || '';
      document.getElementById('explLat').textContent = expl?.latitude ?? '';
      document.getElementById('explLng').textContent = expl?.longitude ?? '';

      const videoUrl = expl?.video_url;
      if (videoUrl) {
        document.getElementById('videoWrap').style.display = 'block';
        const videoEl = document.getElementById('explVideo');
        const linkEl = document.getElementById('explVideoLink');
        const downloadEl = document.getElementById('explVideoDownload');

        const url = String(videoUrl);
        const ext = url.split('?')[0].split('#')[0].split('.').pop()?.toLowerCase?.() || '';
        const mime = ext === 'webm' ? 'video/webm' : ext === 'ogg' || ext === 'ogv' ? 'video/ogg' : 'video/mp4';

        videoEl.removeAttribute('src');
        videoEl.innerHTML = `<source src="${escapeHtml(url)}" type="${escapeHtml(mime)}" />`;
        videoEl.load();

        linkEl.href = url;
        linkEl.style.display = 'inline-block';

        downloadEl.href = '../inc/functions/requete/download_video.php?url=' + encodeURIComponent(url);
        downloadEl.style.display = 'inline-block';
      } else {
        document.getElementById('videoWrap').style.display = 'none';
        const videoEl = document.getElementById('explVideo');
        const linkEl = document.getElementById('explVideoLink');
        const downloadEl = document.getElementById('explVideoDownload');
        videoEl.removeAttribute('src');
        videoEl.innerHTML = '';
        linkEl.href = '#';
        linkEl.style.display = 'none';
        downloadEl.href = '#';
        downloadEl.style.display = 'none';
      }

      const cultures = Array.isArray(planteur?.cultures) ? planteur.cultures : [];
      document.getElementById('culturesTbody').innerHTML = cultures
        .map((c) => {
          return `
            <tr>
              <td>${escapeHtml(c?.type_culture || c?.autre_culture || '')}</td>
              <td>${escapeHtml(c?.superficie_ha ?? '')}</td>
              <td>${escapeHtml(c?.age_culture ?? '')}</td>
              <td>${escapeHtml(c?.mode_culture ?? '')}</td>
              <td>${escapeHtml(c?.production_estimee_kg ?? '')}</td>
            </tr>
          `;
        })
        .join('');

      const info = planteur?.informations || {};
      document.getElementById('infoSemences').textContent = info?.type_semences || '';
      document.getElementById('infoPhyto').textContent = info?.usage_phytosanitaires === true ? 'Oui' : info?.usage_phytosanitaires === false ? 'Non' : '';
      document.getElementById('infoTrav').textContent = info?.nombre_travailleurs ?? '';

      const hasParcelles = Array.isArray(planteur?.parcelles) && planteur.parcelles.length > 0;
      const openBtn = document.getElementById('openParcellesMap');
      openBtn.style.display = hasParcelles ? 'inline-block' : 'none';
      openBtn.onclick = function () {
        $('#parcellesMapModalDetails').modal('show');
        ensureMap();
        drawParcelles(planteur);
      };
    }

    async function load() {
      if (!planteurId) {
        errorEl.textContent = 'ID planteur manquant.';
        errorEl.style.display = 'block';
        loaderEl.style.display = 'none';
        return;
      }

      errorEl.style.display = 'none';
      loaderEl.style.display = 'block';
      contentEl.style.display = 'none';

      try {
        const res = await fetch(`${apiUrl}?id=${encodeURIComponent(planteurId)}`, { cache: 'no-store' });
        const json = await res.json();
        if (!res.ok || !json?.success) {
          throw new Error(json?.error || json?.message || 'Erreur API');
        }

        const planteur = json?.data?.planteurs?.[0] || json?.data;
        if (!planteur || !planteur.id) {
          throw new Error('Planteur introuvable.');
        }

        fill(planteur);
        loaderEl.style.display = 'none';
        contentEl.style.display = 'block';
      } catch (e) {
        errorEl.textContent = e?.message || String(e);
        errorEl.style.display = 'block';
      } finally {
        loaderEl.style.display = 'none';
      }
    }

    load();
  })();
</script>

<?php
include('footer.php');
