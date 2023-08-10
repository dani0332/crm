class CommonPage {

  verifyURL(endPoint) {
    return cy.url().should('include', endPoint)
  }

  getApiIntercept(interceptName, responseCode) {
    cy.wait(interceptName).its('response.statusCode').should('eq', responseCode);
  }

  getFirstNameField(first_name) {
    return cy.get('input#first_name').should('be.visible').clear().type(first_name)
  }

  getLastNameField(last_name) {
    return cy.get('input#last_name').should('be.visible').clear().type(last_name)
  }

  //Get date of birth
  getDOB(month, year, day) {
    cy.get('input#dob').click()
    cy.get('select.ui-datepicker-month').should('be.visible').select(month)
    cy.get('select.ui-datepicker-year').should('be.visible').select(year)
    cy.get('[data-handler="selectDay"]').should('be.visible').eq(day).click()
  }

  getMobileNumber(phoneNumber) {
    return cy.get("input#mobile_no").should('be.visible').clear().type(phoneNumber)
  }

  getEmail(email) {
    return cy.get('input#email').clear().type(email)
  }

  getNationalityId(nationality) {
    return cy.get('select#nationality_id').should('be.visible').select(nationality)
  }

  getSuccessAssertion() {
    return cy.get("div#vehicle_assumptions_success_msg").should("have.text", "Vehicle Assumptions Data Found")
  }

  getEditButton() {
    return cy.get('a#texta').should("be.visible").click()
  }

  getTextFieldByName(fieldName, text) {
    cy.get('h4.text-sm').contains(fieldName).parent().within(() => {
      cy.get('input[type="text"]').should('be.visible').type(text)
    })
  }

  getHeadingAssertion(heading) {
    return cy.get('.x_title').contains(heading).should('be.visible')
  }

  getDropdownByName(dropdownName, value) {
    cy.get('h4.text-sm').contains(dropdownName).parent().within(() => {
      cy.get('.w-full.border.border-gray-300').should('be.visible').click()
      cy.get('span.flex-1.truncate').contains(value).should('be.visible').click()
    })
  }

  getDateTimeDropdown() {
    cy.get('h4.text-sm').contains("DATE OF BIRTH ").parent().within(() => {
      cy.get('svg').should('be.visible').click()
    })
  }

  getNationality(dropdownName, value) {
    cy.get('h4.text-sm').contains(dropdownName).parent().within(() => {
      cy.get('button[id="headlessui-combobox-button-2"]').should('be.visible').click()
      cy.get('span.flex-1.truncate').contains(value).should('be.visible').click()
    })
  }

  getVerifyHealthQuote() {
    cy.get('.grid').containsx(dropdownName).parent().within(() => {
      cy.get('button[id="headlessui-combobox-button-2"]').should('be.visible').click()
      cy.get('span.flex-1.truncate').contains(value).should('be.visible').click()
    })
  }

  getPanelHeadingAssertion(heading) {
    return cy.get('div.x_title').should('be.visible').contains(heading)
  }

  getButtonByName(name) {
    return cy.get('button[type="submit"]').contains(name).should('be.visible').click()
  }

  getPopUpAssertion(text) {
    return cy.get('div.alert.alert-success').contains(text).should('be.visible')
  }

  //Lead Status
  getLeadStatus(leadStatus) {
    return cy.get('select#leadStatus').should('be.visible').select(leadStatus)
  }

  getNotesField(notes) {
    return cy.get('textarea#notes').should('be.visible').clear().type(notes)
  }

  //Add Activity
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

  //Add Additional Contact
  getAdditionalContactButton() {
    return cy.get('button#additional-contact-add-btn').should('be.visible').click()
  }

  getContactType(type) {
    return cy.get('select#additional_contact_type').should('be.visible').select(type)
  }

  getContactFeild(value) {
    return cy.get('input#additional_contact').should('be.visible').type(value)
  }

  getAddContactButton() {
    return cy.get('button#additional-contact-modal-add-btn').should('be.visible').click()
  }

  //Delete Additional Contact
  getDeleteAdditionalContactButton() {
    return cy.get('.btn.btn-danger.btn-sm').contains('Delete').should('be.visible').click()
  }

  //Load History Data 
  getLoadHistoryDataButton() {
    return cy.get('button#loadHistoryDataBtn').should('be.visible').click()
  }

  //View Audit Logs
  getViewAuditLogsButton() {
    return cy.get('button#auditablebtn').should('be.visible').click()
  }


}
export default CommonPage;
