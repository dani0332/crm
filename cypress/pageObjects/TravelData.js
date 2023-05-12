class TravelData {
    getPremiumField(premium) {
        return cy.get('input#premium').should('be.visible').type(premium)
    }

    getDaysCoverForField(days) {
        return cy.get('input#days_cover_for').should('be.visible').type(days)
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
        return cy.get('textarea#details').should('be.visible').type(detail)
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






}
export default TravelData