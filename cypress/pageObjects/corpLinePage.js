class corpLinePage {

    //Search fields
    getCompanyNameField(name) {
        return cy.get('input#company_name').should('be.visible').type(name)
    }

    getPolicyNumberField(policyNumber) {
        return cy.get('input#policy_number').should('be.visible').type(policyNumber)
    }

    getPremiumField() {
        return cy.get('input#premium').should('be.visible')
    }

    getNumberOfEmployeeField() {
        return cy.get('input#number_of_employees').should('be.visible')
    }

    getBusinessInsuranceType() {
        return cy.get('select#business_type_of_insurance_id').should('be.visible')
    }

    getBriefDetailsScreen() {
        return cy.get('textarea#brief_details').should('be.visible')
    }

    getGender() {
        return cy.get('select#gender').should('be.visible')
    }
}

export default corpLinePage;
