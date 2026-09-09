document.addEventListener('DOMContentLoaded', function () {
    const transactionForm = document.querySelector('#transactionModal form');

    if (!transactionForm) {
        return;
    }

    const amountInput = document.getElementById('transaction_amount_visual');
    const amountHiddenInput = document.getElementById('transaction_amount');
    const symbolLabel = document.getElementById('transaction_currency_symbol');
    const typeSelect = document.getElementById('transaction_transaction_type_id');
    const descriptionInput = document.getElementById('transaction_description');
    const referenceInput = document.getElementById('transaction_reference');

    const currencyCode = transactionForm.dataset.currencyCode || '';
    const currencyDecimals = parseInt(transactionForm.dataset.currencyDecimals || '2', 10);
    const currencySymbol = transactionForm.dataset.currencySymbol || currencyCode;
    const currencyLocale = currencyCode === 'JPY' ? 'ja-JP' : currencyCode === 'USD' ? 'en-US' : 'es-CO';
    const currencyFormatter = new Intl.NumberFormat(currencyLocale, {
        minimumFractionDigits: currencyDecimals,
        maximumFractionDigits: currencyDecimals,
    });

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

    const getDigitsOnly = function (value) {
        return String(value ?? '').replace(/[^\d]/g, '');
    };

    const formatAmountValue = function (value) {
        const digits = getDigitsOnly(value);

        if (digits === '') {
            return '';
        }

        const numericValue = Number(digits) / Math.pow(10, currencyDecimals);
        return numericValue.toFixed(currencyDecimals);
    };

    const formatVisibleAmount = function (value) {
        const normalized = String(value ?? '').replace(/[^\d.]/g, '');

        if (normalized === '' || normalized === '.') {
            return '';
        }

        const numericValue = parseFloat(normalized);
        if (Number.isNaN(numericValue)) {
            return '';
        }

        return currencyFormatter.format(numericValue);
    };

    const updateCurrencyFormatting = function () {
        if (symbolLabel) {
            symbolLabel.textContent = currencySymbol || currencyCode || '$';
        }

        amountInput.placeholder = `0.${'0'.repeat(currencyDecimals)}`;
        amountInput.setAttribute('inputmode', 'decimal');

        const currentValue = amountHiddenInput.value;
        if (currentValue) {
            const normalized = formatAmountValue(String(currentValue));
            amountInput.value = formatVisibleAmount(normalized);
            amountHiddenInput.value = normalized;
        }
    };

    const syncAmount = function () {
        const digits = getDigitsOnly(amountInput.value);

        if (digits === '') {
            amountInput.value = '';
            amountHiddenInput.value = '';
            return;
        }

        const numericValue = Number(digits) / Math.pow(10, currencyDecimals);
        const normalizedValue = numericValue.toFixed(currencyDecimals);

        amountHiddenInput.value = normalizedValue;
        amountInput.value = formatVisibleAmount(normalizedValue);
    };

    const validateAmount = function () {
        const normalizedValue = amountHiddenInput.value || amountInput.value;

        if (normalizedValue === '') {
            setError(amountInput, 'El monto es obligatorio.');
            return false;
        }

        const pattern = new RegExp(`^\\d+(?:\\.\\d{0,${currencyDecimals}})?$`);
        if (!pattern.test(String(normalizedValue))) {
            setError(amountInput, 'El monto debe ser numérico.');
            return false;
        }

        clearError(amountInput);
        return true;
    };

    const validateType = function () {
        if (!typeSelect.value) {
            setError(typeSelect, 'Debes seleccionar un tipo de transacción.');
            return false;
        }

        clearError(typeSelect);
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

    amountInput.addEventListener('input', function () {
        const start = amountInput.selectionStart;
        const end = amountInput.selectionEnd;

        syncAmount();
        validateAmount();

        const lengthDelta = amountInput.value.length - (amountInput.value.length - (end - start));
        const newPosition = Math.max(0, Math.min(amountInput.value.length, start + (amountInput.value.length - lengthDelta)));
        amountInput.setSelectionRange(newPosition, newPosition);
    });

    amountInput.addEventListener('blur', function () {
        syncAmount();
    });

    typeSelect.addEventListener('input', validateType);
    descriptionInput.addEventListener('input', validateDescription);
    referenceInput.addEventListener('input', validateReference);

    transactionForm.addEventListener('submit', function (event) {
        syncAmount();
        const isAmountValid = validateAmount();
        const isTypeValid = validateType();
        const isDescriptionValid = validateDescription();
        const isReferenceValid = validateReference();

        if (!isAmountValid || !isTypeValid || !isDescriptionValid || !isReferenceValid) {
            event.preventDefault();
        }
    });

    updateCurrencyFormatting();
});
