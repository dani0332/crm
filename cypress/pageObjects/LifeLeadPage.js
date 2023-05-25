class lifePage {
    getCreateButton() {
        return cy.get('a[href="https://crmstage.alfred.ae/quotes/life/create"]').should('be.visible').click()
    }

    getNationality(value) {
        return cy.get('select#nationality_id').should('be.visible').select(value)
    }

    getSumInsuredValue() {
        return cy.get('input#sum_insured_value').should('be.visible').type('5')
    }

    getPremiumField() {
        return cy.get('input#premium').should('be.visible').type('5')
    }

    getCurrency(currency) {
        return cy.get('select#sum_insured_currency_id').should('be.visible').select(currency)
    }

    getPurposeInsuranceField(purpose) {
        return cy.get('select#purpose_of_insurance_id').should('be.visible').select(purpose)
    }

    getMartialStatus(status) {
        return cy.get('select#marital_status_id').should('be.visible').select(status)
    }

    getChildren(childrens) {
        return cy.get('select#children_id').should('be.visible').select(childrens)
    }

    getTypeOfInsurance(type) {
        return cy.get('select#tenure_of_insurance_id').should('be.visible').select(type)
    }

    getTenure(tenure) {
        return cy.get('select#number_of_years_id').should('be.visible').select(tenure)
    }

    getGender(gender) {
        return cy.get('select#gender').should('be.visible').select(gender)
    }

    getSmoker(smoker) {
        return cy.get('select#is_smoker').should('be.visible').select(smoker)
    }

    getOthersInfo(details) {
        return cy.get('textarea#others_info').should('be.visible').type(details)
    }

    //Upadte Quote Details
    getEditButton(quoteId) {
        return cy.get(`a[href="https://crmstage.alfred.ae/quotes/life${quoteId}/edit"]`).should('be.visible').click()
    }


}
export default lifePage