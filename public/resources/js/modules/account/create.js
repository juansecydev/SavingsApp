"use strict";

import MoneyInput from "../../models/MoneyInput.js";
import CurrencySelect from "../../models/CurrencySelect.js";

document.addEventListener("DOMContentLoaded", function(event) {

    const currencySelectElement = document.getElementById('account_currency_id');
    const moneyInputElement = document.getElementById('account_amount_visual');
    const hiddenMoneyInputElement = document.getElementById('account_amount');
    const currencySymbolElement = document.getElementById('currency-symbol');
    const moneyInput = new MoneyInput(moneyInputElement, hiddenMoneyInputElement, currencySymbolElement);
    const currencySelect = new CurrencySelect(currencySelectElement, moneyInput);

    const form = document.querySelector('form[action="/accounts/create"]');

    if (form) {
        const accountNameInput = document.getElementById('account_name');
        const accountCurrencySelect = document.getElementById('account_currency_id');
        const accountAmountVisible = document.getElementById('account_amount_visual');
        const accountAmountHidden = document.getElementById('account_amount');

        const clearInvalidState = (inputElement) => {
            const feedback = inputElement.closest('.mb-3').querySelector('.invalid-feedback');

            if (feedback) {
                feedback.textContent = '';
            }

            inputElement.classList.remove('is-invalid');
        };

        accountNameInput.addEventListener('input', function() {
            const value = accountNameInput.value.trim();

            if (accountNameInput.classList.contains('is-invalid') && value !== '' && value.length <= 25) {
                clearInvalidState(accountNameInput);
            }
        });

        accountCurrencySelect.addEventListener('change', function() {
            if (accountCurrencySelect.classList.contains('is-invalid') && accountCurrencySelect.value) {
                clearInvalidState(accountCurrencySelect);
            }
        });

        accountAmountVisible.addEventListener('input', function() {
            if (accountAmountVisible.classList.contains('is-invalid')) {
                const hiddenValue = accountAmountHidden.value.trim();

                if (hiddenValue !== '' && hiddenValue !== null) {
                    clearInvalidState(accountAmountVisible);
                }
            }
        });

        form.addEventListener('submit', function(event) {
            const invalidFeedbacks = form.querySelectorAll('.invalid-feedback');
            const accountNameValue = accountNameInput.value.trim();
            const accountCurrencyValue = accountCurrencySelect.value;
            const accountAmountValue = accountAmountHidden.value.trim();

            let isValid = true;

            invalidFeedbacks.forEach((el) => {
                el.textContent = '';
            });

            accountNameInput.classList.remove('is-invalid');
            accountCurrencySelect.classList.remove('is-invalid');
            accountAmountVisible.classList.remove('is-invalid');

            if (accountNameValue === '') {
                const feedback = accountNameInput.parentElement.querySelector('.invalid-feedback');
                feedback.textContent = 'El nombre de la cuenta es obligatorio.';
                accountNameInput.classList.add('is-invalid');
                isValid = false;
            } else if (accountNameValue.length > 25) {
                const feedback = accountNameInput.parentElement.querySelector('.invalid-feedback');
                feedback.textContent = 'El nombre no puede superar 25 caracteres.';
                accountNameInput.classList.add('is-invalid');
                isValid = false;
            }

            if (!accountCurrencyValue) {
                const feedback = accountCurrencySelect.parentElement.querySelector('.invalid-feedback');
                feedback.textContent = 'Debes seleccionar una moneda.';
                accountCurrencySelect.classList.add('is-invalid');
                isValid = false;
            }

            if (accountAmountValue === '' || accountAmountValue === null) {
                const feedback = accountAmountVisible.closest('.mb-3').querySelector('.invalid-feedback');
                feedback.textContent = 'El saldo inicial no puede estar vacío.';
                accountAmountVisible.classList.add('is-invalid');
                isValid = false;
            }

            if (!isValid) {
                event.preventDefault();
                event.stopPropagation();
            }
        });
    }
});