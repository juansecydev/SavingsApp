"use strict";

document.addEventListener("DOMContentLoaded", function(event) {

    document.getElementById('createAccountForm').addEventListener('keydown', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            return false;
        }
    });

    const currencySelect = document.getElementById('account_currency_id');
    const amountInput = document.getElementById('account_amount_visual');
    const amountHiddenInput = document.getElementById('account_amount');
    const amountPreview = document.getElementById('account_amount_preview');
    const locale = navigator.language ?? 'es-CO';

    if (!currencySelect || !amountInput || !amountHiddenInput) {
        return;
    }

    const decimalSeparator = new Intl.NumberFormat(locale)
        .formatToParts(1.1)
        .find(function (part) {
            return part.type === 'decimal';
        })?.value || '.';

    let currencyFormatter = null;
    let currencyDecimals = 2;

    const configureCurrency = function () {
        const selectedOption = currencySelect.options[currencySelect.selectedIndex];
        const moneyCode = selectedOption?.dataset.code;
        const decimals = Number.parseInt(selectedOption?.dataset.decimals || '2', 10);

        if (!moneyCode || !/^[A-Z]{3}$/.test(moneyCode)) {
            currencyFormatter = null;
            currencyDecimals = 2;
            return;
        }

        currencyDecimals = Number.isInteger(decimals) && decimals >= 0 ? decimals : 2;
        currencyFormatter = new Intl.NumberFormat(locale, {
            style: 'currency',
            currency: moneyCode,
            minimumFractionDigits: currencyDecimals,
            maximumFractionDigits: currencyDecimals,
        });
    };

    const updateAmountPreview = function () {
        if (!amountPreview) {
            return;
        }

        const numericValue = Number(amountHiddenInput.value);
        amountPreview.textContent = currencyFormatter && Number.isFinite(numericValue)
            ? currencyFormatter.format(numericValue)
            : 'Selecciona una moneda';
    };

    const updateHiddenAmount = function () {
        const normalizedValue = amountInput.value.replace(decimalSeparator, '.');
        const numericValue = Number(normalizedValue);

        amountHiddenInput.value = normalizedValue === '' || !Number.isFinite(numericValue)
            ? ''
            : numericValue.toFixed(currencyDecimals);
        updateAmountPreview();
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

    currencySelect.addEventListener('change', function () {
        configureCurrency();
        amountHiddenInput.value = (0).toFixed(currencyDecimals);
        amountInput.value = currencyFormatter ? currencyFormatter.format(0) : '0';
        updateAmountPreview();
    });

    configureCurrency();
    sanitizeAmountInput();
    if (currencyFormatter && amountHiddenInput.value !== '') {
        amountInput.value = currencyFormatter.format(Number(amountHiddenInput.value));
    }

});