"use strict";

document.addEventListener('DOMContentLoaded', function () {
    const transactionForm = document.querySelector('#transactionModal form');

    if (!transactionForm) {
        return;
    }

    const accountIdInput = document.getElementById('account_id');
    const csrfTokenInput = transactionForm.querySelector('input[name="csrf_token"]');
    const accountAmount = document.getElementById('account-amount');
    const accountDataError = document.getElementById('account-data-error');
    const transactionsTable = document.getElementById('transactions-table');
    const toastElement = document.getElementById('transaction-toast');
    const toastTitle = document.getElementById('transaction-toast-title');
    const toastMessage = document.getElementById('transaction-toast-message');

    const amountInput = document.getElementById('transaction_amount_visual');
    const amountHiddenInput = document.getElementById('transaction_amount');
    const amountPreview = document.getElementById('transaction_amount_preview');
    const operationSelect = document.getElementById('transaction_operation_id');
    const descriptionInput = document.getElementById('transaction_description');
    const referenceInput = document.getElementById('transaction_reference');

    if (!amountInput || !amountHiddenInput || !operationSelect || !descriptionInput || !referenceInput) {
        return;
    }

    let currencyCode = transactionForm.dataset.currencyCode || '';
    let currencyDecimals = Number.parseInt(transactionForm.dataset.currencyDecimals || '2', 10);
    let currencySymbol = transactionForm.dataset.currencySymbol || currencyCode;
    const currencyLocale = navigator.language ?? 'es-CO';
    const decimalSeparator = new Intl.NumberFormat(currencyLocale)
        .formatToParts(1.1)
        .find(function (part) {
            return part.type === 'decimal';
        })?.value || '.';
    let currencyFormatter = null;

    const configureCurrencyFormatting = function () {
        if (!/^[A-Z]{3}$/.test(currencyCode) || !Number.isInteger(currencyDecimals) || currencyDecimals < 0) {
            currencyFormatter = null;
            return;
        }

        currencyFormatter = new Intl.NumberFormat(currencyLocale, {
            style: 'currency',
            currency: currencyCode,
            minimumFractionDigits: currencyDecimals,
            maximumFractionDigits: currencyDecimals,
        });
    };

    const getFieldWrapper = function (field) {
        return field.closest('.mb-3') || field.parentElement;
    };

    const setError = function (field, message) {
        field.classList.add('is-invalid');
        const wrapper = getFieldWrapper(field);
        const existingFeedback = wrapper.querySelector('.invalid-feedback');

        if (existingFeedback) {
            existingFeedback.textContent = message;
            return;
        }

        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.textContent = message;
        wrapper.appendChild(feedback);
    };

    const clearError = function (field) {
        field.classList.remove('is-invalid');
        const wrapper = getFieldWrapper(field);
        const feedback = wrapper.querySelector('.invalid-feedback');
        if (feedback) {
            feedback.remove();
        }
    };

    const updateCurrencyFormatting = function () {
        amountInput.placeholder = currencyDecimals > 0
            ? `0${decimalSeparator}${'0'.repeat(currencyDecimals)}`
            : '0';
        amountInput.setAttribute('inputmode', 'decimal');

        const amount = Number(amountHiddenInput.value);
        if (currencyFormatter && amountHiddenInput.value !== '' && Number.isFinite(amount)) {
            amountInput.value = currencyFormatter.format(amount);
        }
    };

    const updateHiddenAmount = function () {
        const normalizedValue = amountInput.value.replace(decimalSeparator, '.');
        const numericValue = Number(normalizedValue);

        amountHiddenInput.value = normalizedValue === '' || !Number.isFinite(numericValue)
            ? ''
            : numericValue.toFixed(currencyDecimals);
        updateAmountPreview();
    };

    const updateAmountPreview = function () {
        if (!amountPreview) {
            return;
        }

        const amount = Number(amountHiddenInput.value);
        if (!currencyFormatter || amountHiddenInput.value === '' || !Number.isFinite(amount)) {
            amountPreview.textContent = '';
            return;
        }

        amountPreview.textContent = currencyFormatter
            .formatToParts(amount)
            .map(function (part) {
                return part.type === 'currency' ? currencySymbol : part.value;
            })
            .join('');
    };

    const sanitizeAmountInput = function () {
        let sanitizedValue = '';
        let hasDecimalSeparator = false;

        for (const character of amountInput.value) {
            if (/\d/.test(character)) {
                sanitizedValue += character;
            } else if (
                (character === '.' || character === ',') &&
                !hasDecimalSeparator &&
                currencyDecimals > 0
            ) {
                sanitizedValue += decimalSeparator;
                hasDecimalSeparator = true;
            }
        }

        const [integerPart, fractionalPart] = sanitizedValue.split(decimalSeparator);
        amountInput.value = fractionalPart === undefined
            ? integerPart
            : `${integerPart}${decimalSeparator}${fractionalPart.slice(0, currencyDecimals)}`;
        updateHiddenAmount();
    };

    const showAccountDataError = function (message) {
        if (accountAmount) {
            accountAmount.textContent = 'No disponible';
            accountAmount.classList.remove('text-success', 'text-danger');
        }
        if (!accountDataError) {
            return;
        }

        accountDataError.textContent = message;
        accountDataError.hidden = false;
    };

    const showToast = function (title, message, type) {
        if (!toastElement || !toastTitle || !toastMessage || !window.bootstrap?.Toast) {
            throw new Error('No se pudo mostrar la notificación de la transacción.');
        }

        toastElement.classList.remove('text-bg-success', 'text-bg-danger', 'text-bg-warning');
        toastElement.classList.add(`text-bg-${type}`);
        toastTitle.textContent = title;
        toastMessage.textContent = message;
        window.bootstrap.Toast.getOrCreateInstance(toastElement).show();
    };

    const renderTransactions = function (transactions, formatter, code) {
        if (!transactionsTable) {
            throw new Error('No se encontró la tabla de transacciones.');
        }

        const jquery = window.jQuery;
        if (!jquery || !jquery.fn.DataTable) {
            throw new Error('La librería DataTables no está disponible.');
        }

        if (jquery.fn.DataTable.isDataTable(transactionsTable)) {
            jquery(transactionsTable).DataTable().destroy();
        }

        const rows = transactions.map(function (transaction) {
            const amount = String(transaction.amount || '');

            return [
                String(transaction.date || ''),
                `${String(transaction.type || '')} ${String(transaction.symbol || '')}`.trim(),
                /^-?\d+(?:\.\d+)?$/.test(amount) ? `${formatter.format(amount)} ${code}` : '',
                String(transaction.description || ''),
                String(transaction.reference || ''),
            ];
        });

        jquery(transactionsTable).DataTable({
            data: rows,
            responsive: true,
            scrollX: true,
            paging: true,
            pageLength: 10,
            lengthChange: true,
            ordering: true,
            order: [[0, 'desc']],
            columnDefs: [
                {
                    targets: '_all',
                    render: jquery.fn.dataTable.render.text(),
                },
            ],
            fixedColumns: {
                leftColumns: 1,
            },
            dom: 'Bfrtip',
            buttons: [
                { extend: 'copyHtml5', text: 'Copiar' },
                { extend: 'excelHtml5', text: 'Excel' },
                { extend: 'pdfHtml5', text: 'PDF' },
                { extend: 'print', text: 'Imprimir' },
            ],
            language: {
                decimal: ',',
                thousands: '.',
                processing: 'Procesando...',
                search: 'Buscar global:',
                lengthMenu: 'Mostrar _MENU_ registros',
                info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                infoEmpty: 'Mostrando 0 a 0 de 0 registros',
                infoFiltered: '(filtrado de _MAX_ registros)',
                infoPostFix: '',
                loadingRecords: 'Cargando...',
                zeroRecords: 'No se encontraron registros',
                emptyTable: 'No hay transacciones disponibles',
                paginate: {
                    first: 'Primero',
                    previous: 'Anterior',
                    next: 'Siguiente',
                    last: 'Último',
                },
                aria: {
                    sortAscending: ': activar para ordenar ascendente',
                    sortDescending: ': activar para ordenar descendente',
                },
                buttons: {
                    copyTitle: 'Copiado al portapapeles',
                    copySuccess: { _: '%d líneas copiadas', 1: '1 línea copiada' },
                    copy: 'Copiar',
                    excel: 'Excel',
                    pdf: 'PDF',
                    print: 'Imprimir',
                },
            },
            initComplete: function () {
                this.api().columns().every(function () {
                    const column = this;
                    jquery('input', column.footer()).on('keyup change clear', function () {
                        if (column.search() !== this.value) {
                            column.search(this.value).draw();
                        }
                    });
                });
            },
        });
    };

    const renderAccountData = function (data) {
        const account = data?.account;
        const currency = account?.currency;
        const amount = String(account?.amount ?? '');
        const currencyCodeFromApi = String(currency?.code ?? '');
        const decimals = Number(currency?.minorUnits);

        if (
            !accountAmount ||
            !/^[A-Z]{3}$/.test(currencyCodeFromApi) ||
            !/^-?\d+(?:\.\d+)?$/.test(amount) ||
            !Number.isInteger(decimals) ||
            decimals < 0
        ) {
            throw new Error('La respuesta de la cuenta no tiene un formato válido.');
        }

        currencyCode = currencyCodeFromApi;
        currencyDecimals = decimals;
        currencySymbol = String(currency.symbol ?? '');
        if (currencySymbol === '') {
            throw new Error('La respuesta de la cuenta no incluye el símbolo de la moneda.');
        }
        transactionForm.dataset.currencyCode = currencyCode;
        transactionForm.dataset.currencyDecimals = String(currencyDecimals);
        transactionForm.dataset.currencySymbol = currencySymbol;
        configureCurrencyFormatting();
        updateCurrencyFormatting();
        updateAmountPreview();

        accountAmount.textContent = currencyFormatter.format(amount);
        accountAmount.classList.toggle('text-danger', Number(amount) < 0);
        accountAmount.classList.toggle('text-success', Number(amount) >= 0);

        const transactionAmountFormatter = new Intl.NumberFormat(currencyLocale, {
            minimumFractionDigits: currencyDecimals,
            maximumFractionDigits: currencyDecimals,
        });
        renderTransactions(data.transactions, transactionAmountFormatter, currencyCode);

        if (accountDataError) {
            accountDataError.hidden = true;
            accountDataError.textContent = '';
        }
    };

    const loadAccountData = async function () {
        if (!accountIdInput || !csrfTokenInput || !accountIdInput.value) {
            showAccountDataError('No fue posible identificar la cuenta.');
            return false;
        }

        try {
            const response = await fetch(
                `/api/v1/account/data/${encodeURIComponent(accountIdInput.value)}`,
                {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-Token': csrfTokenInput.value,
                    },
                    credentials: 'same-origin',
                }
            );

            if (!response.headers.get('content-type')?.includes('application/json')) {
                throw new Error('No fue posible cargar los datos. Verifica tu sesión e inténtalo de nuevo.');
            }

            const payload = await response.json();

            if (!response.ok || !payload.data || payload.data.error) {
                throw new Error(payload.data?.error || 'No fue posible cargar los datos de la cuenta.');
            }

            renderAccountData(payload.data);
            return true;
        } catch (error) {
            showAccountDataError(
                error instanceof Error
                    ? error.message
                    : 'No fue posible cargar los datos de la cuenta.'
            );
            return false;
        }
    };

    const validateAmount = function () {
        const normalizedValue = amountHiddenInput.value;

        if (normalizedValue === '') {
            setError(amountInput, 'El monto es obligatorio.');
            return false;
        }

        const pattern = new RegExp(`^\\d+(?:\\.\\d{0,${currencyDecimals}})?$`);
        if (!pattern.test(String(normalizedValue))) {
            setError(amountInput, 'El monto debe ser numérico.');
            return false;
        }
        if (Number(normalizedValue) <= 0) {
            setError(amountInput, 'El monto debe ser mayor que cero.');
            return false;
        }

        clearError(amountInput);
        return true;
    };

    const validateOperation = function () {
        if (!operationSelect.value) {
            setError(operationSelect, 'Debes seleccionar una operación de transacción.');
            return false;
        }

        clearError(operationSelect);
        return true;
    };

    const validateDescription = function () {
        const value = descriptionInput.value.trim();

        if (value === '') {
            setError(descriptionInput, 'La descripción es obligatoria.');
            return false;
        }

        clearError(descriptionInput);
        return true;
    };

    const validateReference = function () {
        const value = referenceInput.value.trim();

        if (value !== '' && value.length > 50) {
            setError(referenceInput, 'La referencia no puede superar 50 caracteres.');
            return false;
        }

        clearError(referenceInput);
        return true;
    };

    amountInput.addEventListener('input', sanitizeAmountInput);
    amountInput.addEventListener('focus', function () {
        const numericValue = Number(amountHiddenInput.value);

        if (amountHiddenInput.value !== '' && Number.isFinite(numericValue)) {
            amountInput.value = numericValue.toFixed(currencyDecimals).replace('.', decimalSeparator);
        }
    });
    amountInput.addEventListener('blur', function () {
        const numericValue = Number(amountHiddenInput.value);

        if (currencyFormatter && amountHiddenInput.value !== '' && Number.isFinite(numericValue)) {
            amountInput.value = currencyFormatter.format(numericValue);
        }
    });

    amountInput.addEventListener('input', function () {
        validateAmount();
    });

    operationSelect.addEventListener('input', validateOperation);
    descriptionInput.addEventListener('input', validateDescription);
    referenceInput.addEventListener('input', validateReference);

    transactionForm.addEventListener('submit', function (event) {
        event.preventDefault();

        const isAmountValid = validateAmount();
        const isOperationValid = validateOperation();
        const isDescriptionValid = validateDescription();
        const isReferenceValid = validateReference();

        if (!isAmountValid || !isOperationValid || !isDescriptionValid || !isReferenceValid) {
            return;
        }

        const submitButton = transactionForm.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
        }

        const submitTransaction = async function () {
            try {
                const response = await fetch(transactionForm.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                        'X-CSRF-Token': csrfTokenInput?.value || '',
                    },
                    body: new URLSearchParams(new FormData(transactionForm)),
                    credentials: 'same-origin',
                });

                if (!response.headers.get('content-type')?.includes('application/json')) {
                    throw new Error('No fue posible procesar la transacción. Verifica tu sesión e inténtalo de nuevo.');
                }

                const payload = await response.json();
                if (!response.ok || !payload.data || payload.data.error) {
                    throw new Error(payload.data?.error || 'No fue posible crear la transacción.');
                }

                transactionForm.reset();
                updateHiddenAmount();
                updateCurrencyFormatting();

                const refreshed = await loadAccountData();
                const modalElement = document.getElementById('transactionModal');
                const modal = modalElement && window.bootstrap?.Modal
                    ? window.bootstrap.Modal.getOrCreateInstance(modalElement)
                    : null;
                modal?.hide();

                showToast(
                    refreshed ? 'Transacción creada' : 'Transacción creada',
                    refreshed
                        ? 'La cuenta y sus transacciones se actualizaron correctamente.'
                        : 'La transacción se guardó, pero no se pudieron actualizar los datos de la cuenta.',
                    refreshed ? 'success' : 'warning'
                );
            } catch (error) {
                showToast(
                    'Error',
                    error instanceof Error ? error.message : 'No fue posible crear la transacción.',
                    'danger'
                );
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                }
            }
        };

        void submitTransaction();
    });

    updateCurrencyFormatting();
    loadAccountData();
});
