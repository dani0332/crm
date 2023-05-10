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

    getYearModal() {
        return cy.get('div[class="dp__month_year_select"]').eq(1).should('be.visible').click()
    }

    getYear() {
        return cy.get('div[data-test="1999"]').should('be.visible').click()
    }

    getDate() {
        return cy.get('.dp__cell_inner.dp__pointer.dp__date_hover').eq(7).should('be.visible').click()

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
        return cy.get('a[href="/quotes/health/GVMSBC8N"]').should('be.visible').click()
    }

    getEditButton() {
        return cy.get('.x-button._button_179kk_2').contains("Edit").click()
    }

    getNationalityValue(value) {
        return cy.get('[role="none"] input[type="text"] ').type(value)
    }

    selectNationality() {
        return cy.get('span.flex-1.truncate.py-px').click()
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

    getAddMemberNationalityDropdown(dropdownName) {
        cy.get('.font-medium.text-gray-800').contains(dropdownName).parent().within(() => {
            cy.get('#headlessui-combobox-button-6').should('be.visible').click()
        })
    }

    getAddMemberDropdown(dropdownName) {
        cy.get('.font-medium.text-gray-800').contains(dropdownName).parent().within(() => {
            cy.get('.w-full.border.border-gray-300').should('be.visible').click()
        })
    }

    getAddMemberDropdownVisa(value) {
        return cy.get('.flex-1.truncate').contains(value).click()
    }

    getAddMemberDOB(dropdownName) {
        cy.get('.font-medium.text-gray-800').contains(dropdownName).scrollIntoView().parent().within(() => {
            cy.get('.appearance-none.block.w-full.placeholder-gray-400').should('be.visible').click()
        })
    }

    getLeadStatusField(dropdownName) {
        cy.get('.font-medium.text-gray-800').contains(dropdownName).parent().within(() => {
            cy.get('.resize-none').should('be.visible').clear().type("Lorem Ipsum")
        })
    }

    getLeadStatusDropdown(dropdownName) {
        return cy.get('.font-medium.text-gray-800').contains(dropdownName).parent().within(() => {
            cy.get('.w-full.border.border-gray-300').should('be.visible').click()
        })
    }

    getLeadStatusDropdownValue() {
        return cy.get('.flex-1.truncate').contains("New Lead").click()
    }

    getAddMemberDropdowns(dropdown) {
        return cy.get('span.flex-1.truncate').contains(dropdown).click()
    }

    getButtonByName(buttonName) {
        return cy.get('button[type="button"]').contains(buttonName).click()
    }

    getAssertionByName(tabName) {
        return cy.get(".rounded-lg.px-3.py-2").contains(tabName).should('be.visible').click()
    }

    getCloseButton() {
        return cy.get('.flex.absolute.p-1').should("be.visible").click()
    }

    getLeadActivityField(feildName, Value) {
        cy.get('.font-medium.text-gray-800').contains(feildName).parent().within(() => {
            cy.get('.appearance-none.block').should('be.visible').type(Value)
        })
    }

    getLeadAssignee() {
        return cy.get('div[role="dialog"] span.flex-1.truncate').contains("EBP Advisor").click()
    }

    getLeadDueDate() {
        return cy.get('.dp__cell_inner.dp__pointer.dp__date_hover').eq(25).click()
    }

    getDialogueDeleteButton() {
        return cy.get('div[role="dialog"] button.x-button').contains("Delete").click()
    }

    getAddAdditionalContactButton() {
        cy.get('.font-medium.text-gray-800').contains(" Customer Additional Contacts ").parent().within(() => {
            cy.get('button[type="button"]').should('be.visible').click()
        })
    }

    getAddAdditionalContactvalueField() {
        cy.get('.font-medium.text-gray-800').contains("Value").parent().within(() => {
            cy.get('.appearance-none.block.w-full.placeholder-gray-400').should('be.visible').type("12345678910")
        })
    }

    getButtonByName(buttonName) {
        return cy.get('button[type="button"]').contains(buttonName).click()
    }

    getAddAdditionalContactButton() {
        return cy.get('.x-button._button_179kk_2.relative').contains(" Add Additional Contacts ").click()
    }

    selectMobileNumber() {
        return cy.get('.flex-1.truncate').contains('Mobile Number').click()
    }

    getAdditionalContactSubmitButton() {
        return cy.get('button[type="submit"]').should('be.visible').click()
    }

}
export default HealthLeadPage;
