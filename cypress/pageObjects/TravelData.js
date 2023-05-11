class TravelData {
    getPremiumField(premium) {
        return cy.get('input#premium').should('be.visible').clear().type(premium)
    }

    getDaysCoverForField(days) {
        return cy.get('input#days_cover_for').should('be.visible').clear().type(days)
    }

    getDestinationId(destination) {
        return cy.get('select#destination_id').should('be.visible').select(destination)
    }

    getRegionsCoverFor(value) {
        return cy.get('select#region_cover_for_id').should('be.visible').select(value)
    }

    getTravelCoverFor(value) {
        return cy.get('select#travel_cover_for_id').should('be.visible').select(value)
    }

    getDescriptionField(detail) {
        return cy.get('textarea#details').should('be.visible').clear().type(detail)
    }

    getCurrentlyLocated(value) {
        return cy.get('select#currently_located_in_id').should('be.visible').select(value)
    }

    getCreateButton(name) {
        return cy.get('button[type="submit"]').contains(name).click()
    }

    //Update Lead Status
    getleadStatusDropdown(status) {
        return cy.get('select#leadStatus').should('be.visible').select(status)
    }

    getNotesField(notes) {
        return cy.get('textarea#notes').should('be.visible').type(notes)
    }

    getPopUpAssertion(text) {
        return cy.get('div.alert.alert-success').contains(text).should('be.visible')
    }

    //Add Member
    getAddMemberButton() {
        return cy.get('button#add-edit-travel-members-btn').should('be.visible').click()
    }

    getAddMemberPopUpAssertion() {
        return cy.get('h5#duplicateLeadModalLabel').contains('New Members').should('be.visible').click()
    }

    getMemberDOB() {
        return cy.get('input[type="date"]').should('be.visible').type('14')
    }

    getDeleteButton() {
        return cy.get('button#member-details-delete-btn').should('be.visible').eq(1).click()
    }

    getPanelAssertion(name) {
        return cy.get('div.x_title').should('be.visible').contains(name).click()
    }

    //Available plans
    getCoopyLinkButton() {
        return cy.get('button#quotePlansGenerateButton').should('be.visible').click()
    }

    getCopiedAssertion() {
        return cy.get('span#quotePlansGenerateMsg').contains('Copied').should('be.visible')
    }

    //Add Activity button
    getActivityButton() {
        return cy.get('button#add-activity-btn').should('be.visible').click()
    }

    getModalAssertion() {
        return cy.get('div.modal-header').contains('New Lead Activity').should('be.visible')
    }

    getTitleField(title) {
        return cy.get('input#email').should('be.visible').type(title, { force: true })
    }

    getActivityDescriptionField(description) {
        return cy.get('textarea#description').should('be.visible').type(description, { force: true })
    }

    getAsigneeDropdown(assignee) {
        return cy.get('select#activity-assignee').should('be.visible').select(assignee)
    }

    getDueDateField() {
        return cy.get('input#due_date').should('be.visible').click()
    }

    selectDate(date) {
        return cy.get('.available').eq(date).click()
    }

    getApplyButton() {
        return cy.get('button[type="button"]').contains('Apply').should('be.visible').click()
    }

    //Delete Activity
    getDeleteActivityButton() {
        return cy.get('button#activity-edit-btn').contains('Delete').should('be.visible').click()
    }

    //Add Additional Contact button
    getAdditionalContactButton() {
        return cy.get('button#additional-contact-add-btn').should('be.visible').click()
    }

    getAdditionalContactType(email) {
        return cy.get('select#additional_contact_type').should('be.visible').select(email)
    }

    getValueField(value) {
        return cy.get('input#additional_contact').should('be.visible').type(value)
    }

    //Delete Activity button
    getDeleteAdditionalContactButton() {
        return cy.get('.btn.btn-danger.btn-sm').should('be.visible').click()
    }

    //Load History Button
    getLoadHistoryButton() {
        return cy.get('button#loadHistoryDataBtn').should('be.visible').click()
    }

    //View Audit Logs button
    getViewAuditLogButton() {
        return cy.get('button#auditablebtn').should('be.visible').click()
    }

    //Edit Quote Details
    getEditButton() {
        return cy.get('a[href="https://crmstage.alfred.ae/quotes/travel/HFLWTV3Y/edit"]').should('be.visible').click()
    }

}
export default TravelData