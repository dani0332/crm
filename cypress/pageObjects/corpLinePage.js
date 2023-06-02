class corpLinePage {

    //Search fields
    getCompanyNameField(name) {
        return cy.get('input#company_name').should('be.visible').clear().type(name)
    }

    getPolicyNumberField(policyNumber) {
        return cy.get('input#policy_number').should('be.visible').clear().type(policyNumber)
    }

    getPremiumField() {
        return cy.get('input#premium').should('be.visible').clear()
    }

    getNumberOfEmployeeField() {
        return cy.get('input#number_of_employees').should('be.visible').clear()
    }

    getBusinessInsuranceType() {
        return cy.get('select#business_type_of_insurance_id').should('be.visible')
    }

    getBriefDetailsScreen() {
        return cy.get('textarea#brief_details').should('be.visible').clear()
    }

    getGender() {
        return cy.get('select#gender').should('be.visible')
    }

    //Edit Quote Details
    getEditButton() {
        return cy.get('a#texta').should('be.visible')
    }

    //Update Lead Status
    getLeadStatusDropdown() {
        return cy.get('select#leadStatus').should('be.visible')
    }

    getNotesField() {
        return cy.get('textarea#notes').should('be.visible')
    }

    //Get Add Activity Button
    getAddActivityButton() {
        return cy.get('button#add-activity-btn').should('be.visible')
    }

    getActivityTitleField() {
        return cy.get('input#email').should('be.visible')
    }

    getActivityDescriptionField() {
        return cy.get('textarea#description').should('be.visible')
    }

    getActivityAssignee() {
        return cy.get('select#activity-assignee').should('be.visible')
    }

    getDeleteActivityButton() {
        return cy.get('button#activity-edit-btn').contains('Delete').click()
    }

    //Add Additional Contact
    getAdditionalContact() {
        return cy.get('button#additional-contact-add-btn').should('be.visible')
    }

    getContactType() {
        return cy.get('select#additional_contact_type').should('be.visible')
    }

    getValueField() {
        return cy.get('input#additional_contact').should('be.visible')
    }

}

export default corpLinePage;
