// =============================================================================
// PROYECTO: SysVentas - Sistema de Punto de Venta de Mostrador (POS)
// INSTITUCIÓN: CBTis No. 19 "Miguel Hidalgo y Costilla" - Colima, Col., México
// ARCHIVO: public/assets/js/pos.js
// DESCRIPCIÓN: Dinamismo del mostrador con JavaScript, reactividad y validaciones (Fase 7).
// =============================================================================

document.addEventListener('DOMContentLoaded', () => {
    // -------------------------------------------------------------------------
    // 1. SELECTORES DE ELEMENTOS DEL DOM
    // -------------------------------------------------------------------------
    const barcodeInput = document.getElementById('barcode-input');
    const btnClearSearch = document.getElementById('btn-clear-search');
    const productsGrid = document.getElementById('products-grid');
    const ticketBody = document.getElementById('ticket-items-body');
    const lblSubtotal = document.getElementById('lbl-subtotal');
    const lblIva = document.getElementById('lbl-iva');
    const lblGranTotal = document.getElementById('lbl-gran-total');
    const totalArticulos = document.getElementById('total-articulos');
    const btnAbrirCobro = document.getElementById('btn-abrir-cobro');
    const btnCancelarVenta = document.getElementById('btn-cancelar-venta');

    // Elementos del Modal de Cobro
    const modalCobroElement = document.getElementById('modalCobro');
    const modalCobroInstance = modalCobroElement ? new bootstrap.Modal(modalCobroElement) : null;
    const modalTotalDisplay = document.getElementById('modal-total-display');
    const pagoConInput = document.getElementById('pago-con-input');
    const modalCambioDisplay = document.getElementById('modal-cambio-display');
    const labelCambioTitulo = document.getElementById('label-cambio-titulo');
    const boxCambio = document.getElementById('box-cambio');
    const btnConfirmarVenta = document.getElementById('btn-confirmar-venta');
    const quickCashContainer = document.getElementById('quick-cash-container');
    const radioMetodos = document.querySelectorAll('input[name="metodo_pago"]');
    const categoryButtons = document.querySelectorAll('.category-pills button');

    // -------------------------------------------------------------------------
    // 2. ESTADO DEL CARRITO EN MEMORIA
    // -------------------------------------------------------------------------
    const cart = new Map();

    // Función auxiliar de formateo monetario MXN
    const formatMoney = (amount) => {
        return new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN',
            minimumFractionDigits: 2
        }).format(amount);
    };

    // -------------------------------------------------------------------------
    // 3. FILTRADO POR CATEGORÍAS (PILLS)
    // -------------------------------------------------------------------------
    categoryButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            categoryButtons.forEach(b => {
                b.classList.remove('btn-primary', 'active');
                b.classList.add('btn-outline-secondary');
            });
            btn.classList.remove('btn-outline-secondary');
            btn.classList.add('btn-primary', 'active');

            const selectedCat = btn.getAttribute('data-category');
            filterProductsGrid();
        });
    });

    // -------------------------------------------------------------------------
    // 4. BÚSQUEDA PREDICTIVA EN TIEMPO REAL Y LECTURA CON PISTOLA DE CÓDIGO
    // -------------------------------------------------------------------------
    function filterProductsGrid() {
        const query = (barcodeInput ? barcodeInput.value : '').toLowerCase().trim();
        const activeBtn = document.querySelector('.category-pills button.active');
        const selectedCat = activeBtn ? activeBtn.getAttribute('data-category') : 'ALL';
        const items = productsGrid ? productsGrid.querySelectorAll('.product-item') : [];

        items.forEach(item => {
            const name = (item.getAttribute('data-name') || '').toLowerCase();
            const barcode = (item.getAttribute('data-barcode') || '').toLowerCase();
            const category = item.getAttribute('data-category') || '';

            const matchesQuery = name.includes(query) || barcode.includes(query);
            const matchesCat = (selectedCat === 'ALL' || category === selectedCat);

            if (matchesQuery && matchesCat) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }

    if (barcodeInput) {
        barcodeInput.addEventListener('input', filterProductsGrid);

        // Limpiar búsqueda
        if (btnClearSearch) {
            btnClearSearch.addEventListener('click', () => {
                barcodeInput.value = '';
                filterProductsGrid();
                barcodeInput.focus();
            });
        }

        // Manejo del evento ENTER (Pistola de código de barras)
        barcodeInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const code = barcodeInput.value.trim().toLowerCase();
                if (!code) return;

                // Buscar coincidencia exacta por código de barras
                const items = Array.from(productsGrid.querySelectorAll('.product-item'));
                const exactMatch = items.find(it => (it.getAttribute('data-barcode') || '').toLowerCase() === code);

                if (exactMatch) {
                    addProductFromCard(exactMatch);
                    barcodeInput.value = '';
                    filterProductsGrid();
                } else {
                    // Si no hay código exacto, verificar si queda exactamente 1 producto visible
                    const visibleItems = items.filter(it => it.style.display !== 'none');
                    if (visibleItems.length === 1) {
                        addProductFromCard(visibleItems[0]);
                        barcodeInput.value = '';
                        filterProductsGrid();
                    } else {
                        // Alerta visual de producto no encontrado
                        barcodeInput.classList.add('is-invalid');
                        setTimeout(() => barcodeInput.classList.remove('is-invalid'), 1200);
                    }
                }
            }
        });
    }

    // -------------------------------------------------------------------------
    // 5. AGREGAR PRODUCTOS AL CARRITO (CLIC EN CATÁLOGO)
    // -------------------------------------------------------------------------
    if (productsGrid) {
        productsGrid.addEventListener('click', (e) => {
            const card = e.target.closest('.product-item');
            if (!card) return;
            addProductFromCard(card);
        });
    }

    function addProductFromCard(card) {
        const id = parseInt(card.getAttribute('data-id'));
        const name = card.getAttribute('data-name');
        const barcode = card.getAttribute('data-barcode');
        const price = parseFloat(card.getAttribute('data-price') || 0);
        const stock = parseInt(card.getAttribute('data-stock') || 0);

        if (stock <= 0) {
            alert(`El producto "${name}" se encuentra AGOTADO en inventario.`);
            return;
        }

        if (cart.has(id)) {
            const item = cart.get(id);
            if (item.qty + 1 > stock) {
                alert(`Existencias insuficientes. Máximo disponible: ${stock} piezas.`);
                return;
            }
            item.qty += 1;
        } else {
            cart.set(id, {
                id: id,
                barcode: barcode,
                name: name,
                price: price,
                stock: stock,
                qty: 1
            });
        }

        renderCart();
    }

    // -------------------------------------------------------------------------
    // 6. RENDERIZACIÓN REACTIVA DEL TICKET Y TOTALIZADOR
    // -------------------------------------------------------------------------
    function renderCart() {
        if (!ticketBody) return;
        ticketBody.innerHTML = '';

        if (cart.size === 0) {
            ticketBody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center py-5 text-muted">
                        <i class="bi bi-cart-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                        No hay productos en la cuenta actual.<br>
                        <small>Escanee un código o seleccione del catálogo.</small>
                    </td>
                </tr>
            `;
            updateTotals(0, 0);
            return;
        }

        let totalGeneral = 0;
        let totalPzas = 0;

        cart.forEach((item) => {
            const itemTotal = item.qty * item.price;
            totalGeneral += itemTotal;
            totalPzas += item.qty;

            const tr = document.createElement('tr');
            tr.setAttribute('data-id', item.id);
            tr.innerHTML = `
                <td class="ps-2">
                    <div class="fw-semibold text-truncate small" style="max-width: 170px;" title="${item.name}">${item.name}</div>
                    <small class="text-muted font-monospace">${formatMoney(item.price)} c/u</small>
                </td>
                <td class="text-center">
                    <div class="input-group input-group-sm justify-content-center">
                        <button class="btn btn-outline-secondary btn-decrease px-2" type="button" data-id="${item.id}">-</button>
                        <input type="text" class="form-control text-center item-qty px-1 font-monospace fw-bold" 
                               value="${item.qty}" data-id="${item.id}" style="max-width: 44px;">
                        <button class="btn btn-outline-secondary btn-increase px-2" type="button" data-id="${item.id}">+</button>
                    </div>
                </td>
                <td class="text-end fw-bold text-dark font-monospace pe-2">${formatMoney(itemTotal)}</td>
                <td class="text-center pe-2">
                    <button class="btn btn-sm btn-link text-danger p-0 btn-remove-item" data-id="${item.id}" title="Eliminar fila">
                        <i class="bi bi-x-circle-fill"></i>
                    </button>
                </td>
            `;
            ticketBody.appendChild(tr);
        });

        updateTotals(totalGeneral, totalPzas);
    }

    function updateTotals(total, count) {
        const subtotal = total / 1.16;
        const iva = total - subtotal;

        if (lblSubtotal) lblSubtotal.textContent = formatMoney(subtotal);
        if (lblIva) lblIva.textContent = formatMoney(iva);
        if (lblGranTotal) lblGranTotal.textContent = formatMoney(total);
        if (totalArticulos) totalArticulos.textContent = `${count} ${count === 1 ? 'pieza' : 'piezas'}`;
        if (modalTotalDisplay) modalTotalDisplay.textContent = formatMoney(total);

        // Habilitar o deshabilitar botón de cobro
        if (btnAbrirCobro) {
            btnAbrirCobro.disabled = (total <= 0);
        }

        // Actualizar botón de Monto Exacto en el modal
        const btnExacto = document.querySelector('.btn-quick-cash[data-cash="exacto"]');
        if (btnExacto) {
            btnExacto.textContent = `Exacto (${formatMoney(total)})`;
        }

        recalculateChange();
    }

    // -------------------------------------------------------------------------
    // 7. EVENTOS DE TABLA: INCREMENTAR, DECREMENTAR, CAMBIO DIRECTO Y ELIMINAR
    // -------------------------------------------------------------------------
    if (ticketBody) {
        ticketBody.addEventListener('click', (e) => {
            const btnInc = e.target.closest('.btn-increase');
            const btnDec = e.target.closest('.btn-decrease');
            const btnRem = e.target.closest('.btn-remove-item');

            if (btnInc) {
                const id = parseInt(btnInc.getAttribute('data-id'));
                const item = cart.get(id);
                if (item) {
                    if (item.qty + 1 > item.stock) {
                        alert(`Stock máximo alcanzado (${item.stock} piezas).`);
                        return;
                    }
                    item.qty += 1;
                    renderCart();
                }
            } else if (btnDec) {
                const id = parseInt(btnDec.getAttribute('data-id'));
                const item = cart.get(id);
                if (item) {
                    if (item.qty > 1) {
                        item.qty -= 1;
                    } else {
                        cart.delete(id);
                    }
                    renderCart();
                }
            } else if (btnRem) {
                const id = parseInt(btnRem.getAttribute('data-id'));
                cart.delete(id);
                renderCart();
            }
        });

        // Edición manual del campo numérico de cantidad
        ticketBody.addEventListener('change', (e) => {
            const qtyInput = e.target.closest('.item-qty');
            if (!qtyInput) return;

            const id = parseInt(qtyInput.getAttribute('data-id'));
            const item = cart.get(id);
            if (!item) return;

            let newQty = parseInt(qtyInput.value) || 1;
            if (newQty < 1) newQty = 1;

            if (newQty > item.stock) {
                alert(`Stock insuficiente. Solo hay ${item.stock} unidades disponibles.`);
                newQty = item.stock;
            }

            item.qty = newQty;
            renderCart();
        });
    }

    // -------------------------------------------------------------------------
    // 8. CÁLCULO DINÁMICO DE CAMBIO Y BILLETES RÁPIDOS EN MODAL DE COBRO
    // -------------------------------------------------------------------------
    function getTotalValue() {
        let total = 0;
        cart.forEach(it => total += it.qty * it.price);
        return total;
    }

    function recalculateChange() {
        const total = getTotalValue();
        const metodo = document.querySelector('input[name="metodo_pago"]:checked')?.value || 'EFECTIVO';

        if (metodo === 'TARJETA' || metodo === 'TRANSFERENCIA') {
            if (pagoConInput) pagoConInput.value = total.toFixed(2);
            if (modalCambioDisplay) modalCambioDisplay.textContent = formatMoney(0);
            if (labelCambioTitulo) labelCambioTitulo.textContent = "Pago Electrónico Completo";
            if (boxCambio) boxCambio.className = "card bg-primary bg-opacity-10 border-primary text-center py-2 mb-2";
            if (btnConfirmarVenta) btnConfirmarVenta.disabled = (total <= 0);
            return;
        }

        // Modo Efectivo
        if (labelCambioTitulo) labelCambioTitulo.textContent = "Cambio para el Cliente";
        const recibido = parseFloat(pagoConInput ? pagoConInput.value : 0) || 0;
        const cambio = recibido - total;

        if (!btnConfirmarVenta || !boxCambio || !modalCambioDisplay) return;

        if (total <= 0) {
            modalCambioDisplay.textContent = formatMoney(0);
            boxCambio.className = "card bg-light border text-center py-2 mb-2";
            btnConfirmarVenta.disabled = true;
        } else if (cambio >= 0) {
            modalCambioDisplay.textContent = formatMoney(cambio);
            boxCambio.className = "card bg-success bg-opacity-10 border-success text-center py-2 mb-2";
            modalCambioDisplay.className = "display-6 fw-bold text-success font-monospace mb-0";
            btnConfirmarVenta.disabled = false;
        } else {
            modalCambioDisplay.textContent = `Faltan ${formatMoney(Math.abs(cambio))}`;
            boxCambio.className = "card bg-danger bg-opacity-10 border-danger text-center py-2 mb-2";
            modalCambioDisplay.className = "display-6 fw-bold text-danger font-monospace mb-0";
            btnConfirmarVenta.disabled = true;
        }
    }

    if (pagoConInput) {
        pagoConInput.addEventListener('input', recalculateChange);
    }

    // Alternar método de pago
    radioMetodos.forEach(radio => {
        radio.addEventListener('change', () => {
            const isEfectivo = (radio.value === 'EFECTIVO');
            if (quickCashContainer) {
                quickCashContainer.style.display = isEfectivo ? 'block' : 'none';
            }
            if (pagoConInput) {
                pagoConInput.readOnly = !isEfectivo;
            }
            recalculateChange();
        });
    });

    // Botones de billetes rápidos
    document.querySelectorAll('.btn-quick-cash').forEach(btn => {
        btn.addEventListener('click', () => {
            const cashType = btn.getAttribute('data-cash');
            const total = getTotalValue();

            if (cashType === 'exacto') {
                if (pagoConInput) pagoConInput.value = total.toFixed(2);
            } else {
                if (pagoConInput) pagoConInput.value = parseFloat(cashType).toFixed(2);
            }
            recalculateChange();
        });
    });

    // Foco automático en el input de pago al abrir el modal
    if (modalCobroElement) {
        modalCobroElement.addEventListener('shown.bs.modal', () => {
            const total = getTotalValue();
            if (pagoConInput) {
                pagoConInput.value = total.toFixed(2); // Inicializa con monto exacto
                pagoConInput.focus();
                pagoConInput.select();
            }
            recalculateChange();
        });
    }

    // -------------------------------------------------------------------------
    // 9. CONFIRMACIÓN DE VENTA / EMISIÓN DE TICKET (TRANSACCIÓN ATÓMICA FASE 8)
    // -------------------------------------------------------------------------
    if (btnConfirmarVenta) {
        btnConfirmarVenta.addEventListener('click', async () => {
            const total = getTotalValue();
            const recibido = parseFloat(pagoConInput ? pagoConInput.value : 0) || total;
            const cambio = Math.max(0, recibido - total);
            const metodo = document.querySelector('input[name="metodo_pago"]:checked')?.value || 'EFECTIVO';

            // Preparar items para el payload
            const items = [];
            cart.forEach(it => {
                items.push({
                    id: it.id,
                    barcode: it.barcode,
                    name: it.name,
                    precio: it.price,
                    cantidad: it.qty
                });
            });

            btnConfirmarVenta.disabled = true;
            btnConfirmarVenta.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Procesando...';

            try {
                const response = await fetch('?c=pos&a=procesarVenta', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        total: total,
                        pago_con: recibido,
                        cambio: cambio,
                        metodo_pago: metodo,
                        items: items
                    })
                });

                const res = await response.json();

                if (res.success) {
                    // Cerrar modal de cobro
                    if (modalCobroInstance) modalCobroInstance.hide();

                    // Rellenar datos en el modal de ticket térmico
                    const ticketModalEl = document.getElementById('modalTicketImpresion');
                    if (ticketModalEl) {
                        const elFolio = document.getElementById('ticket-folio');
                        const elSubtotal = document.getElementById('ticket-subtotal');
                        const elIva = document.getElementById('ticket-iva');
                        const elTotal = document.getElementById('ticket-total');
                        const elPago = document.getElementById('ticket-pago-con');
                        const elCambio = document.getElementById('ticket-cambio');
                        const elMetodo = document.getElementById('ticket-metodo');

                        if (elFolio) elFolio.textContent = res.folio;
                        if (elSubtotal) elSubtotal.textContent = formatMoney(total / 1.16);
                        if (elIva) elIva.textContent = formatMoney(total - (total / 1.16));
                        if (elTotal) elTotal.textContent = formatMoney(total);
                        if (elPago) elPago.textContent = formatMoney(recibido);
                        if (elCambio) elCambio.textContent = formatMoney(cambio);
                        if (elMetodo) elMetodo.textContent = metodo;

                        const ticketItemsBody = document.getElementById('ticket-body-articulos');
                        if (ticketItemsBody) {
                            ticketItemsBody.innerHTML = '';
                            items.forEach(it => {
                                const row = document.createElement('tr');
                                row.innerHTML = `
                                    <td>${it.cantidad}x</td>
                                    <td class="text-truncate" style="max-width: 140px;">${it.name}</td>
                                    <td class="text-end">${formatMoney(it.cantidad * it.precio)}</td>
                                `;
                                ticketItemsBody.appendChild(row);
                            });
                        }

                        const modalTicket = new bootstrap.Modal(ticketModalEl);
                        modalTicket.show();
                    }

                    // Actualizar reactivamente el stock de los productos vendidos en la interfaz del mostrador
                    items.forEach(soldItem => {
                        const card = productsGrid ? productsGrid.querySelector(`.product-item[data-id="${soldItem.id}"]`) : null;
                        if (card) {
                            const currentStock = parseInt(card.getAttribute('data-stock') || 0);
                            const newStock = Math.max(0, currentStock - soldItem.cantidad);
                            card.setAttribute('data-stock', newStock);

                            const badge = card.querySelector('.badge');
                            if (badge) {
                                badge.className = `badge ${newStock <= 0 ? 'badge-stock-out' : (newStock <= 5 ? 'badge-stock-critical' : 'badge-stock-normal')} small`;
                                badge.textContent = newStock <= 0 ? 'Agotado (0)' : (newStock <= 5 ? `Bajo (${newStock})` : `Stock: ${newStock}`);
                            }

                            const cardInner = card.querySelector('.card');
                            if (cardInner) {
                                if (newStock <= 0) {
                                    cardInner.classList.add('opacity-50');
                                    cardInner.style.cursor = 'not-allowed';
                                } else {
                                    cardInner.classList.remove('opacity-50');
                                    cardInner.style.cursor = 'pointer';
                                }
                            }
                        }
                    });

                    // Limpiar carrito y resetear terminal
                    cart.clear();
                    renderCart();
                    if (barcodeInput) barcodeInput.focus();
                } else {
                    alert('Error en la transacción: ' + (res.message || 'No se pudo completar la venta.'));
                }
            } catch (err) {
                alert('Ocurrió un error al comunicar con el servidor.');
            } finally {
                btnConfirmarVenta.disabled = false;
                btnConfirmarVenta.innerHTML = '<i class="bi bi-printer me-1"></i> Confirmar y Emitir Ticket';
            }
        });
    }

    // -------------------------------------------------------------------------
    // 10. BOTÓN LIMPIAR VENTA CON CONFIRMACIÓN (F2) Y ATAJOS DE TECLADO
    // -------------------------------------------------------------------------
    if (btnCancelarVenta) {
        btnCancelarVenta.addEventListener('click', () => {
            if (cart.size === 0) return;
            if (confirm("¿Está seguro de cancelar y limpiar la cuenta actual en curso?")) {
                cart.clear();
                renderCart();
                if (barcodeInput) barcodeInput.focus();
            }
        });
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'F2') {
            e.preventDefault();
            if (btnCancelarVenta) btnCancelarVenta.click();
        } else if (e.key === 'F3') {
            e.preventDefault();
            if (barcodeInput) {
                barcodeInput.focus();
                barcodeInput.select();
            }
        } else if (e.key === 'F10') {
            e.preventDefault();
            if (btnAbrirCobro && !btnAbrirCobro.disabled) {
                btnAbrirCobro.click();
            }
        }
    });

    // Inicializar la tabla en vacío al cargar
    renderCart();
});
