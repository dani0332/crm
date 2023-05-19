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

    getOpenQuoteButton(Id) {
        return cy.get(`a[href="https://crmstage.alfred.ae/quotes/health/${Id}"]`).should('be.visible').click()
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
            cy.get('.appearance-none.block.placeholder-gray-400').should('be.visible').click()
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
            cy.get('.appearance-none.block.w-full.placeholder-gray-400').should('be.visible').type("9875032151")
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

    //Delete Activity Button
    getDeleteButton() {
        return cy.get('button[type="button"]').contains('Delete').should('be.visible').click()
    }

    getModalDeleteButton() {
        return cy.get('div[role="dialog"] button.x-button').contains('Delete').click()
    }

    //Health Quote Plans
    getViewPlansButton() {
        return cy.get('.flex.gap-2.pr-2 span').should('be.visible').eq(0).click()
    }
    //General Info tab assertions
    getGeneralTab() {
        return cy.get('button[id="headlessui-tabs-tab-3"]').contains('General Info').should('be.visible').click()
    }

    getTabMembersAssertion(name) {
        return cy.get('dt.font-medium').contains(name).should('be.visible')
    }

    //Members tab
    getMemberstab() {
        return cy.get('button[id="headlessui-tabs-tab-4"]').contains('Members').should('be.visible').click()
    }

    getMemberAsertions(name) {
        return cy.get('th.py-2.font-semibold').contains(name).should('be.visible')
    }

    //In Patient Tab
    getInPatientTab() {
        return cy.get('button[id="headlessui-tabs-tab-5"]').contains('In Patient').should('be.visible').click()
    }

    getInPatientAssertions(names) {
        return cy.get('dt.font-medium.mb-1').contains(names).should('be.visible')
    }

    //Out Patient Tab
    getOutPatientTab() {
        return cy.get('button[id="headlessui-tabs-tab-6"]').contains('Out Patient').should('be.visible').click()
    }

    //Region coverage & Network list
    getRegionCoverageTab() {
        return cy.get('button[id="headlessui-tabs-tab-7"]').contains('Region coverage & Network list').should('be.visible').click()
    }

    //Co-pay/Co-insurance
    getCopayCoInsuranceTab() {
        return cy.get('[id="headlessui-tabs-tab-8"]').contains('Co-pay/Co-insurance').should('be.visible').click()
    }

    //Maternity cover
    getMaternityTab() {
        return cy.get('button[id="headlessui-tabs-tab-9"]').contains('Maternity cover').should('be.visible').click()
    }

    //Health plan toggle button
    getToggleButton() {
        return cy.get('.flex.items-center.rounded-full.transition-colors').should('be.visible').click()
    }

    //close pop up Plan
    getCloseButton() {
        return cy.get('div.flex.absolute.p-1').should('be.visible').click()
    }


}
export default HealthLeadPage;
