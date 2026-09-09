export default class MoneyInput
{
    constructor(input, hidden, symbolElement = null, currency = null, decimals = 0)
    {
        this.input = input;
        this.hidden = hidden;
        this.symbolElement = symbolElement;
        this.currency = currency;
        this.formatter = null;
        this.decimals = decimals;
        this.eventsBound = false;

        // Bind the event handler so `this` always refers to the MoneyInput instance.
        this.handleInput = this.handleInput.bind(this);
    }

    bindEvents()
    {
        if (this.eventsBound) {
            return;
        }

        this.input.addEventListener('input', this.handleInput);
        this.eventsBound = true;
    }

    setCurrency(currency)
    {
        this.currency = currency;
        this.updateSymbol();
    }

    handleInput(event)
    {
        const value = event.target.value || this.input.value;
        const digits = value
            .replace(/[^\d]/g, '');

        this.hidden.value = digits === '' ? '0' : digits;

        this.render(digits);
    }

    render(minorUnits)
    {
        const value = Number(minorUnits || 0);

        const amount =
            value /
            Math.pow(
                10,
                this.decimals
            );

        this.formatter =
            new Intl.NumberFormat(
                'es-CO', // Todo: Make this dynamic based on the user's locale
                {
                    minimumFractionDigits: this.decimals,
                    maximumFractionDigits: this.decimals
                }
            );

        this.input.value = this.formatter.format(amount);
        this.updateSymbol();
    }

    updateSymbol()
    {
        if (!this.symbolElement || !this.currency) {
            return;
        }

        const formatter = new Intl.NumberFormat(
            'es-CO',
            {
                style: 'currency',
                currency: this.currency,
                minimumFractionDigits: this.decimals,
                maximumFractionDigits: this.decimals
            }
        );

        const parts = formatter.formatToParts(0);
        const currencyPart = parts.find(part => part.type === 'currency');

        this.symbolElement.textContent = currencyPart ? currencyPart.value : this.currency;
    }

    getMinorUnits()
    {
        return Number(
            this.hidden.value || 0
        );
    }

    setDisabled(isDisabled){
        this.input.disabled = isDisabled;
    }

    setDecimals(decimals){
        this.decimals = decimals;
    }

    reset()
    {
        this.hidden.value = 0;
        this.render(0);
    }
    
    removeEvents(){
        if (!this.eventsBound) {
            return;
        }

        this.input.removeEventListener('input', this.handleInput);
        this.eventsBound = false;
    }
}