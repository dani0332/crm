class HealthLeadPage {

    getEmailField(email) {
        return cy.get('input[type="email"]').should('be.visible').type(email)
    }

    getMobileNumberField(phoneNumber) {
        return cy.get('input[type="tel"]').should('be.visible').type(phoneNumber)
    }

    getNationality() {
        return cy.get('input[id="headlessui-combobox-input-5"]').click()
    }

    getSubmitButton() {
        return cy.get('button[type="submit"]').should('be.visible').click()
    }

    getYearModal() {
        return cy.get('div[class="dp__month_year_select"]').eq(1).should('be.visible').click()
    }

    getYear() {
        return cy.get('div[data-test="1999"]').should('be.visible').click()
    }

    getDate() {
        return cy.get('[class="dp__cell_inner dp__pointer dp__date_hover"]').eq(7).should('be.visible').click()

    }

    getTextFieldByName(fieldName, text) {
        cy.get('.font-medium.text-gray-800').contains(fieldName).parent().within(() => {
            cy.get('input[type="search"]').should('be.visible').clear().type(text)
        })
    }

    getSearchButton() {
        return cy.get('button[type="submit"]').should('be.visible').click()
    }

    getResetButton() {
        return cy.get('button[type="button"]').contains(' Reset ').click()
    }

    getDateTimeDropdown() {
        cy.get('.font-medium.text-gray-800').contains("DATE OF BIRTH").parent().within(() => {
            cy.get('input[type="date"]').should('be.visible').click()
        })
    }

    getHealthDetailDropdown(dropdownName) {
        cy.get('.font-medium.text-gray-800').contains(dropdownName).parent().within(() => {
            cy.get('.w-full.border.border-gray-300').should('be.visible').click()
        })
    }

    getNationalityDropdown(dropdownName) {
        cy.get('.font-medium.text-gray-800').contains(dropdownName).parent().within(() => {
            cy.get('button[type="button"]').should('be.visible').click()
        })
    }

    getHealthTextFieldByName(fieldName, text) {
        cy.get('.font-medium.text-gray-800').contains(fieldName).parent().within(() => {
            cy.get('input[type="text"]').should('be.visible').clear().type(text)
        })
    }

    getMemberCategoryDropdown(dropdownName) {
        cy.get('.font-medium.text-gray-800').contains(dropdownName).parent().within(() => {
            cy.get('.w-full.border.border-gray-300').should('be.visible').click()
        })
    }

    getOpenQuoteButton() {
        return cy.get('a[href="/quotes/health/7G94MWVL"]').should('be.visible').click()
    }

    getEditButton() {
        return cy.get('a[href="7G94MWVL/edit"]').should("be.visible").click()
    }

    getNationalityValue() {
        return cy.get('.flex-1.truncate.py-px').contains("Pakistani").click()
    }

    getEmirateOfVisaValue() {
        return cy.get('.flex-1.truncate').contains("Dubai").click()
    }

    getMemberCategoryValue() {
        return cy.get('.flex-1.truncate').contains("Employee").click()
    }

    getSubmitButton() {
        return cy.get('button[type="submit"]').should('be.visible').click()

    }

    getAssignSubTeamDropdown(dropdownName) {
        return cy.get('.font-medium.text-gray-800').contains(dropdownName).parent().within(() => {
            cy.get('.inline-block.relative.x-popover').should('be.visible').click()
        })
    }

    getButtonByName(buttonName) {
        return cy.get('button[type="button"]').contains(buttonName).parent().within(() => {
            cy.get('data-v-dc323f72').should('be.visible').click()
        })
    }

    getAddMemberButton() {
        return cy.get('button[type="button"]').contains(' Add Member ').click()
    }

}
export default HealthLeadPage;
