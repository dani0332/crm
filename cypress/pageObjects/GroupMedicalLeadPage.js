class HealthLead {

    //Create Lead
    getCreateLeadPageButton() {
        return cy.get('a[href="https://crmstage.alfred.ae/medical/amt/create"]').should('be.visible').click()
    }

    getCompanyNameField(companyName) {
        return cy.get('input#company_name').should('be.visible').clear().type(companyName)
    }

    getNumberOfEmployeesFeild(number) {
        return cy.get('input#number_of_employees').should('be.visible').clear().type(number)
    }

    getBusinessInsuranceType() {
        return cy.get('select[id="business_type_of_insurance_id"]').should('be.visible').select('Group Medical')
    }

    getBriefDetailsField(details) {
        return cy.get('textarea#brief_details').should('be.visible').type(details)
    }

    getAssignLeadDropdown(assignee) {
        return cy.get('select#assigned_to_id_new').should('be.visible').select(assignee)
    }

    getAssignButton() {
        return cy.get('button#assignAfterTeam').should('be.visible').click()
    }


    //Retrieve Quote 
    getCdbID(quoteId) {
        return cy.get('input#code').should('be.visible').type('BUS-' + quoteId)
    }

    getSearchButton() {
        return cy.get('input#amt_sbmt').should('be.visible').click()
    }

    assertCDBID(quoteId) {
        return cy.get(`a[href="/medical/amt/${quoteId}"]`).contains(`BUS-${quoteId}`).should('be.visible')
    }

    getSearchResetButton() {
        return cy.get('input#amt_reset').should('be.visible').click()
    }

    getFirstNameField(name) {
        return cy.get('input#first_name').should('be.visible').type(name)
    }

    getLastNameField(lastName) {
        return cy.get('input#last_name').should('be.visible').type(lastName)
    }

    getEmailField() {
        return cy.get('input#email').should('be.visible').type('im.automation4@gmial.com')
    }

    getLeadDropdown(leadStatus) {
        return cy.get('select#leadStatus').should('be.visible').select(leadStatus)
    }




    //Edit Quote Details
    getEditButton(quoteId) {
        return cy.get(`a[href="https://crmstage.alfred.ae/medical/amt${quoteId}/edit"]`).should('be.visible').click()
    }

    getGroupMedicalType(type) {
        return cy.get('select#group_medical_type_id').should('be.visible').select(type)
    }

    getPremiumField(premium) {
        return cy.get('input#premium').should('be.visible').type(premium)
    }

    //Lead Status
    getLeadStatusDropdown(value) {
        return cy.get('select#leadStatus').should('be.visible').select(value)
    }

    getNotesField(details) {
        return cy.get('textarea#notes').should('be.visible').type(details)
    }

    // Add Acitivity 
    gteAddActivityButton() {
        return cy.get('button#add-activity-btn').should('be.visible').click()
    }

    getAvtivityTitleField(value) {
        return cy.get('input#email').should('be.visible').clear().type(value)
    }

    getDescriptionField(description) {
        return cy.get('textarea#description').should('be.visible').clear().type(description)
    }

    getActivityAssignee(assignee) {
        return cy.get('select#activity-assignee').should('be.visible').select(assignee)
    }

    //Edit&Delete activity 
    getActivityButtons(name) {
        return cy.get('button#activity-edit-btn').contains(name).should('be.visible').click()
    }

    getTitleField(title) {
        return cy.get('input#title').should('be.visible').clear().type(title)
    }

    getDescriptionField(desc) {
        return cy.get('textarea#description').eq(0).should('be.visible').clear().type(desc)
    }

    getEditAssignee(assignee) {
        return cy.get('select#assignee_id').should('be.visible').select(assignee)
    }

    //Delete Activity 
    getDeleteActivityButton() {
        return cy.get('button#activity-edit-btn').contains('Delete').click()
    }

    //Add Additional Contact
    getAdditionalContactButton() {
        return cy.get('button#additional-contact-add-btn').should('be.visible').click()
    }

    getAdditionalContactTypeButton(type) {
        return cy.get('select#additional_contact_type').should('be.visible').select(type)
    }

    getValueField(value) {
        return cy.get('input#additional_contact').should('be.visible').type(value)
    }

    //Delete Additional Contact
    getDeleteContactButton() {
        return cy.get('.btn.btn-danger.btn-sm').contains('Delete').should('be.visible').click()
    }




}
export default HealthLead;
