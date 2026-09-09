export default class CurrencySelect
{
    constructor(input, moneyInput)
    {
        this.input = input;
        this.id = 0;
        this.code = null;
        this.moneyInput = moneyInput;
        this.decimals = 0;
        this.initialize();
        this.validateIfCurrencyIsEmpty();
    }

    initialize()
    {
        this.input.addEventListener('change', (e) => {

            this.id = e.target.value || null;
            this.code = e.target.options[e.target.selectedIndex].dataset.code || null;
            this.decimals = e.target.options[e.target.selectedIndex].dataset.decimals || 0;
            this.validateIfCurrencyIsEmpty();
            
            if(this.code && this.decimals) {
                this.moneyInput.setCurrency(this.code);
                this.moneyInput.setDecimals(this.decimals);
                this.moneyInput.reset();
                this.moneyInput.bindEvents();
            }else{
                this.moneyInput.removeEvents();
            }

        });
    }

    getId()
    {
        return this.input.id;
    }

    getCode()
    {
        return this.code;
    }

    validateIfCurrencyIsEmpty()
    {
        if (!this.code || !this.id) {
            this.moneyInput.setDisabled(true);
        } else {
            this.moneyInput.setDisabled(false);
        }
    }

}