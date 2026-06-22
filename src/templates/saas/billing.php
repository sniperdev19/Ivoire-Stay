<?php
/** @var string $base_url */
if (!isset($base_url)) $base_url = rtrim(APP_URL, '/');
$defaultTab = $defaultTab ?? 'invoices';
?>
<?php $pageJs = 'saas-billing'; ?>

<div x-data="billingPage('<?= $base_url ?>', '<?= $defaultTab ?>')"
     x-init="init()"
     @keydown.escape.window="showModal = false">

  <!-- ── Blur-gate ────────────────────────────────────────── -->
  <div style="position:relative;min-height:520px;">
    <div :style="upgradeRequired ? 'filter:blur(6px);pointer-events:none;user-select:none;' : ''">

      <!-- HEADER -->
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
        <div>
          <h1 style="margin:0;font-size:20px;font-weight:700;color:#111827;">Comptabilité</h1>
          <p style="margin:4px 0 0;font-size:13px;color:#9CA3AF;">Factures & Paiements</p>
        </div>
        <div style="display:flex;gap:8px;">
          <button class="btn-saas-secondary" @click="openCreateInvoice()">+ Nouvelle facture</button>
          <button class="btn-saas-primary"   @click="openCreatePayment()">+ Enregistrer un paiement</button>
        </div>
      </div>

      <!-- KPI BAR -->
      <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:20px;">
        <div class="kpi-card saas-card">
          <div style="font-size:11px;color:#9CA3AF;margin-bottom:4px;">Total factures</div>
          <div style="font-size:20px;font-weight:800;color:#111827;" x-text="invoices.length"></div>
        </div>
        <div class="kpi-card saas-card">
          <div style="font-size:11px;color:#9CA3AF;margin-bottom:4px;">Montant total TTC</div>
          <div style="font-size:16px;font-weight:800;color:#1B4332;" x-text="formatPrice(kpi.totalTtc)"></div>
        </div>
        <div class="kpi-card saas-card">
          <div style="font-size:11px;color:#9CA3AF;margin-bottom:4px;">Factures payées</div>
          <div style="font-size:20px;font-weight:800;color:#16a34a;" x-text="kpi.paidInv"></div>
        </div>
        <div class="kpi-card saas-card">
          <div style="font-size:11px;color:#9CA3AF;margin-bottom:4px;">Encaissé</div>
          <div style="font-size:16px;font-weight:800;color:#1B4332;" x-text="formatPrice(kpi.encaisse)"></div>
        </div>
        <div class="kpi-card saas-card">
          <div style="font-size:11px;color:#9CA3AF;margin-bottom:4px;">En attente</div>
          <div style="font-size:16px;font-weight:800;color:#D97706;" x-text="formatPrice(kpi.enAttente)"></div>
        </div>
      </div>

      <!-- TABS -->
      <div style="display:flex;gap:0;border-bottom:2px solid rgba(0,0,0,0.07);margin-bottom:16px;">
        <button
          @click="activeTab='invoices'"
          :style="activeTab==='invoices'
            ? 'border:none;background:none;padding:10px 24px;font-size:13px;font-weight:700;color:#1B4332;border-bottom:2px solid #C9A84C;margin-bottom:-2px;cursor:pointer;'
            : 'border:none;background:none;padding:10px 24px;font-size:13px;font-weight:500;color:#9CA3AF;cursor:pointer;'">
          Factures
          <span style="margin-left:6px;background:rgba(0,0,0,0.07);border-radius:20px;padding:1px 7px;font-size:11px;" x-text="invoices.length"></span>
        </button>
        <button
          @click="activeTab='payments'"
          :style="activeTab==='payments'
            ? 'border:none;background:none;padding:10px 24px;font-size:13px;font-weight:700;color:#1B4332;border-bottom:2px solid #C9A84C;margin-bottom:-2px;cursor:pointer;'
            : 'border:none;background:none;padding:10px 24px;font-size:13px;font-weight:500;color:#9CA3AF;cursor:pointer;'">
          Paiements
          <span style="margin-left:6px;background:rgba(0,0,0,0.07);border-radius:20px;padding:1px 7px;font-size:11px;" x-text="payments.length"></span>
        </button>
      </div>

      <!-- Erreur -->
      <div x-show="error && !loading"
           style="padding:12px 16px;margin-bottom:12px;background:rgba(220,38,38,0.06);border:1px solid rgba(220,38,38,0.15);border-radius:10px;color:#B91C1C;font-size:13px;"
           x-text="error">
      </div>

      <!-- ═══════════════ TAB : FACTURES ═══════════════ -->
      <div x-show="activeTab === 'invoices'">

        <!-- Filtres -->
        <div class="saas-card" style="padding:10px;margin-bottom:12px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
          <input class="saas-input" placeholder="Client ou n° facture…" x-model="invSearch" style="flex:1;min-width:200px;" @keydown.enter="invPage=1; loadInvoices()" />
          <select class="saas-input" x-model="invStatusFilter" style="width:150px;">
            <option value="">Tous statuts</option>
            <option value="draft">Brouillon</option>
            <option value="sent">Envoyée</option>
            <option value="paid">Payée</option>
          </select>
          <button class="btn-saas-primary" @click="invPage=1; loadInvoices()">Filtrer</button>
        </div>

        <!-- Table factures -->
        <div class="saas-card" style="padding:0;overflow:hidden;">
          <!-- Skeleton -->
          <template x-if="loading">
            <div style="padding:12px;">
              <template x-for="i in [1,2,3,4,5]" :key="i">
                <div style="display:flex;gap:12px;padding:12px 0;border-bottom:1px solid rgba(0,0,0,0.04);">
                  <div class="cl-shimmer" style="width:110px;height:14px;border-radius:6px;"></div>
                  <div class="cl-shimmer" style="flex:1;height:14px;border-radius:6px;"></div>
                  <div class="cl-shimmer" style="width:80px;height:14px;border-radius:6px;"></div>
                  <div class="cl-shimmer" style="width:70px;height:14px;border-radius:6px;"></div>
                </div>
              </template>
            </div>
          </template>

          <template x-if="!loading">
            <div style="overflow-x:auto;">
              <table class="saas-table" style="width:100%;">
                <thead>
                  <tr>
                    <th>N° Facture</th>
                    <th>Client</th>
                    <th>Montant HT</th>
                    <th>TVA</th>
                    <th>Montant TTC</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <template x-if="invoices.length === 0">
                    <tr><td colspan="8" style="text-align:center;padding:40px;color:#9CA3AF;">
                      <div>
                        <svg xmlns="http://www.w3.org/2000/svg" style="width:32px;height:32px;color:#C9A84C;margin-bottom:8px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <div style="font-weight:600;color:#374151;">Aucune facture</div>
                        <div style="font-size:12px;margin-top:4px;">Les factures sont créées automatiquement à chaque réservation.</div>
                      </div>
                    </td></tr>
                  </template>
                  <template x-for="inv in invoices" :key="inv.id">
                    <tr>
                      <td><span style="font-family:Cormorant,serif;font-size:15px;color:#C9A84C;font-weight:700;" x-text="inv.invoice_number"></span></td>
                      <td>
                        <div style="font-weight:600;color:#111827;" x-text="inv.client_name ?? '—'"></div>
                        <div style="font-size:11px;color:#9CA3AF;" x-text="inv.client_email ?? ''"></div>
                      </td>
                      <td x-text="formatPrice(inv.amount_ht)"></td>
                      <td x-text="(inv.tax_rate ?? 0) + '%'"></td>
                      <td style="font-weight:800;color:#1B4332;" x-text="formatPrice(inv.amount_ttc)"></td>
                      <td><span :class="invStatusCfg(inv.status).badge" x-text="invStatusCfg(inv.status).label"></span></td>
                      <td x-text="formatDate(inv.created_at)"></td>
                      <td style="white-space:nowrap;display:flex;gap:6px;">
                        <button class="btn-saas-secondary" @click.stop="downloadPdf(inv.id)" title="Télécharger PDF">
                          <svg xmlns="http://www.w3.org/2000/svg" style="width:13px;height:13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                          PDF
                        </button>
                        <button class="btn-saas-secondary" @click.stop="openEditInvoice(inv)">Modifier</button>
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
          </template>
        </div>

      </div><!-- /tab factures -->

      <!-- ═══════════════ TAB : PAIEMENTS ═══════════════ -->
      <div x-show="activeTab === 'payments'">

        <!-- Filtres -->
        <div class="saas-card" style="padding:10px;margin-bottom:12px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
          <select class="saas-input" x-model="payMethodFilter" style="width:170px;">
            <option value="">Méthode (Toutes)</option>
            <option value="mobile_money">Mobile Money</option>
            <option value="cash">Espèces</option>
            <option value="card">Carte</option>
            <option value="bank_transfer">Virement</option>
          </select>
          <select class="saas-input" x-model="payStatusFilter" style="width:160px;">
            <option value="">Statut (Tous)</option>
            <option value="completed">Complété</option>
            <option value="pending">En attente</option>
            <option value="refunded">Remboursé</option>
          </select>
          <button class="btn-saas-primary" @click="loadPayments()">Filtrer</button>
        </div>

        <!-- Table paiements -->
        <div class="saas-card" style="padding:0;overflow:hidden;">
          <template x-if="loading">
            <div style="padding:12px;">
              <template x-for="i in [1,2,3,4,5]" :key="i">
                <div style="display:flex;gap:12px;padding:12px 0;border-bottom:1px solid rgba(0,0,0,0.04);">
                  <div class="cl-shimmer" style="width:100px;height:14px;border-radius:6px;"></div>
                  <div class="cl-shimmer" style="flex:1;height:14px;border-radius:6px;"></div>
                  <div class="cl-shimmer" style="width:80px;height:14px;border-radius:6px;"></div>
                  <div class="cl-shimmer" style="width:70px;height:14px;border-radius:6px;"></div>
                </div>
              </template>
            </div>
          </template>

          <template x-if="!loading">
            <div style="overflow-x:auto;">
              <table class="saas-table" style="width:100%;">
                <thead>
                  <tr>
                    <th>Référence</th>
                    <th>Client</th>
                    <th>Facture</th>
                    <th>Montant</th>
                    <th>Méthode</th>
                    <th>Type</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <template x-if="payments.length === 0">
                    <tr><td colspan="9" style="text-align:center;padding:40px;color:#9CA3AF;">
                      <div>
                        <svg xmlns="http://www.w3.org/2000/svg" style="width:32px;height:32px;color:#C9A84C;margin-bottom:8px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        <div style="font-weight:600;color:#374151;">Aucun paiement</div>
                        <div style="font-size:12px;margin-top:4px;">Cliquez sur « Enregistrer un paiement » pour commencer.</div>
                      </div>
                    </td></tr>
                  </template>
                  <template x-for="p in payments" :key="p.id">
                    <tr>
                      <td><span style="display:inline-block;padding:4px 8px;border-radius:6px;background:rgba(201,168,76,0.1);font-weight:700;color:#C9A84C;font-size:12px;" x-text="p.reference ?? '#' + p.id"></span></td>
                      <td style="font-weight:600;" x-text="p.client_name ?? '—'"></td>
                      <td style="font-size:12px;color:#6B7280;" x-text="p.invoice_number ?? '—'"></td>
                      <td style="font-weight:800;" x-text="formatPrice(p.amount)"></td>
                      <td>
                        <div style="display:inline-flex;align-items:center;gap:6px;">
                          <div :style="'width:8px;height:8px;border-radius:3px;background:' + methodColor(p.method)"></div>
                          <span x-text="methodLabel(p.method)"></span>
                        </div>
                      </td>
                      <td style="font-size:12px;color:#6B7280;" x-text="typeLabel(p.type)"></td>
                      <td><span :class="payStatusCfg(p.status).badge" x-text="payStatusCfg(p.status).label"></span></td>
                      <td x-text="formatDate(p.created_at)"></td>
                      <td>
                        <button class="btn-saas-secondary" @click.stop="openEditPayment(p)">Modifier</button>
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
          </template>
        </div>

      </div><!-- /tab paiements -->

    </div><!-- /contenu flouté -->

    <!-- Overlay upgrade -->
    <div x-show="!loading && upgradeRequired"
         style="position:absolute;inset:0;z-index:10;background:rgba(248,250,252,0.55);">
      <div style="display:flex;align-items:center;justify-content:center;min-height:100%;padding:24px;">
        <div style="background:white;border-radius:20px;padding:36px 40px;text-align:center;max-width:380px;width:100%;box-shadow:0 8px 40px rgba(0,0,0,0.18);border:1px solid rgba(0,0,0,0.06);">
          <div style="width:64px;height:64px;border-radius:18px;background:#0F2B20;display:grid;place-items:center;margin:0 auto 20px;">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:30px;height:30px;color:#C9A84C;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          </div>
          <div style="display:inline-block;background:#FEF3C7;color:#92400E;font-size:11px;font-weight:700;padding:3px 12px;border-radius:20px;margin-bottom:14px;letter-spacing:0.5px;">PLAN PRO REQUIS</div>
          <h2 style="margin:0 0 10px;font-size:20px;font-weight:800;color:#111827;">Comptabilité</h2>
          <p style="color:#6B7280;font-size:13px;margin:0 auto 24px;line-height:1.6;">Gérez vos factures et encaissements. Disponible à partir du plan <strong style="color:#1B4332;">Pro</strong>.</p>
          <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
            <a href="<?= $base_url ?>/saas/settings" style="display:inline-flex;align-items:center;gap:6px;padding:10px 20px;background:#1B4332;color:white;border-radius:10px;font-size:14px;font-weight:600;text-decoration:none;">Passer au Pro →</a>
            <a href="<?= $base_url ?>/saas" style="display:inline-flex;align-items:center;padding:10px 20px;background:white;color:#374151;border:1px solid #E5E7EB;border-radius:10px;font-size:14px;font-weight:600;text-decoration:none;">Tableau de bord</a>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /blur-gate -->

  <!-- ═══════════ MODAL FACTURE ═══════════ -->
  <div x-cloak x-show="showModal && modalType === 'invoice'" class="saas-modal-bg" @click.self="showModal=false">
    <div class="saas-modal" style="max-width:540px;" @click.stop>
      <div class="saas-modal-header">
        <h2 x-text="editing ? 'Modifier la facture' : 'Nouvelle facture'"></h2>
        <button @click="showModal=false" style="background:none;border:none;cursor:pointer;color:#6B7280;">
          <svg xmlns="http://www.w3.org/2000/svg" style="width:18px;height:18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="saas-modal-body">
        <div style="display:grid;gap:14px;">
          <template x-if="!editing">
            <div>
              <label class="saas-label">Réservation *</label>
              <select class="saas-input" x-model="invForm.booking_id">
                <option value="">Sélectionner une réservation</option>
                <template x-for="b in bookingsWithoutInvoice" :key="b.id">
                  <option :value="b.id" x-text="'#' + b.id + ' — ' + (b.client_name ?? 'Client') + ' (' + (b.check_in ?? '') + ')'"></option>
                </template>
              </select>
              <div x-show="bookingsWithoutInvoice.length === 0" style="font-size:12px;color:#9CA3AF;margin-top:4px;">Aucune réservation sans facture.</div>
            </div>
          </template>
          <div style="display:grid;grid-template-columns:1fr 120px;gap:12px;">
            <div>
              <label class="saas-label">Montant HT</label>
              <input class="saas-input" type="number" x-model.number="invForm.amount_ht" placeholder="Calculé depuis la réservation" />
            </div>
            <div>
              <label class="saas-label">TVA (%)</label>
              <input class="saas-input" type="number" x-model.number="invForm.tax_rate" />
            </div>
          </div>
          <div x-show="invForm.amount_ht > 0" style="background:rgba(201,168,76,0.06);padding:10px 14px;border-radius:8px;display:flex;justify-content:space-between;">
            <span style="font-size:13px;color:#6B7280;">Montant TTC estimé</span>
            <span style="font-weight:800;color:#1B4332;" x-text="formatPrice(invAmountTtc)"></span>
          </div>
          <div>
            <label class="saas-label">Statut</label>
            <select class="saas-input" x-model="invForm.status">
              <option value="draft">Brouillon</option>
              <option value="sent">Envoyée</option>
              <option value="paid">Payée</option>
            </select>
          </div>
          <template x-if="formError">
            <div style="background:rgba(220,38,38,0.06);border:1px solid rgba(220,38,38,0.12);padding:10px;border-radius:8px;color:#DC2626;font-size:13px;" x-text="formError"></div>
          </template>
        </div>
      </div>
      <div class="saas-modal-footer">
        <button class="btn-saas-secondary" @click="showModal=false">Annuler</button>
        <button class="btn-saas-primary" @click="saveInvoice()" :disabled="submitting">
          <span x-show="!submitting">Enregistrer</span>
          <div x-show="submitting" style="width:14px;height:14px;border-radius:50%;border:2px solid rgba(255,255,255,0.3);border-top-color:white;animation:spin 0.7s linear infinite;"></div>
        </button>
      </div>
    </div>
  </div>

  <!-- ═══════════ MODAL PAIEMENT ═══════════ -->
  <div x-cloak x-show="showModal && modalType === 'payment'" class="saas-modal-bg" @click.self="showModal=false">
    <div class="saas-modal" style="max-width:520px;" @click.stop>
      <div class="saas-modal-header">
        <h2 x-text="editing ? 'Modifier le paiement' : 'Enregistrer un paiement'"></h2>
        <button @click="showModal=false" style="background:none;border:none;cursor:pointer;color:#6B7280;">
          <svg xmlns="http://www.w3.org/2000/svg" style="width:18px;height:18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="saas-modal-body">
        <div style="display:grid;gap:14px;">
          <template x-if="!editing">
            <div>
              <label class="saas-label">Facture *</label>
              <select class="saas-input" x-model="payForm.invoice_id" @change="onInvoiceChange()">
                <option value="">Sélectionner une facture</option>
                <template x-for="inv in invoices.filter(i => i.status !== 'paid')" :key="inv.id">
                  <option :value="inv.id" x-text="(inv.invoice_number ?? '#' + inv.id) + ' — ' + (inv.client_name ?? '') + ' — ' + formatPrice(inv.amount_ttc)"></option>
                </template>
              </select>
            </div>
          </template>
          <div>
            <label class="saas-label">Montant</label>
            <input class="saas-input" type="number" x-model.number="payForm.amount" placeholder="0" />
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div>
              <label class="saas-label">Méthode</label>
              <select class="saas-input" x-model="payForm.method">
                <option value="mobile_money">Mobile Money</option>
                <option value="cash">Espèces</option>
                <option value="card">Carte</option>
                <option value="bank_transfer">Virement</option>
              </select>
            </div>
            <div>
              <label class="saas-label">Type</label>
              <select class="saas-input" x-model="payForm.type">
                <option value="full">Paiement complet</option>
                <option value="deposit">Acompte</option>
                <option value="partial">Partiel</option>
              </select>
            </div>
          </div>
          <div>
            <label class="saas-label">Notes</label>
            <textarea class="saas-input" rows="2" x-model="payForm.notes" placeholder="Optionnel…"></textarea>
          </div>
          <template x-if="formError">
            <div style="background:rgba(220,38,38,0.06);border:1px solid rgba(220,38,38,0.12);padding:10px;border-radius:8px;color:#DC2626;font-size:13px;" x-text="formError"></div>
          </template>
        </div>
      </div>
      <div class="saas-modal-footer">
        <button class="btn-saas-secondary" @click="showModal=false">Annuler</button>
        <button class="btn-saas-primary" @click="savePayment()" :disabled="submitting">
          <span x-show="!submitting">Enregistrer</span>
          <div x-show="submitting" style="width:14px;height:14px;border-radius:50%;border:2px solid rgba(255,255,255,0.3);border-top-color:white;animation:spin 0.7s linear infinite;"></div>
        </button>
      </div>
    </div>
  </div>

</div>
