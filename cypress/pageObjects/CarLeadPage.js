class CarLeadPage {


  //Search fields
  searchUsingCDBID(cdbId) {
    return cy.get('input#code').type(cdbId)
  }

  searchUsingFirstname(firstName) {
    return cy.get("input#first_name").type(firstName)
  }

  searchUsingLastname(lastName) {
    return cy.get("input#last_name").type(lastName)
  }

  searchUsingEmail(email) {
    return cy.get("input#email").type(email)
  }

  searchUsingMobileNumber(mobileNumber) {
    return cy.get("input#mobile_no").type(mobileNumber)
  }

  //Create Lead Button
  getCreateLeadButton() {
    return cy.get('a[href="https://crmstage.alfred.ae/quotes/car/create"]').contains('Create Lead').click()
  }

  //Select Reason
  getReferral() {
    return cy.get('.row.radio-reason-manual-lead [type="radio"]').should('be.visible').eq(0).click()
  }

  getConfirmButton() {
    return cy.get('[type="button"]').should('be.visible').contains('Confirm').click()
  }



  getUaeLicenseHeldFor(licenseHeld) {
    return cy.get('select#uae_license_held_for_id').should('be.visible').select(licenseHeld)
  }

  getBackHomeLicense(backHomeLicense) {
    return cy.get('#back_home_license_held_for_id').should('be.visible').select(backHomeLicense)
  }

  getCarMakeId(carMakeId) {
    return cy.get('#car_make_id').should('be.visible').select(carMakeId)
  }

  getCarModelId(carModelId) {
    return cy.get("div[id='car_model_id_div'] [name='car_model_id']").should('be.visible').select(carModelId)
  }

  getManufacturYear(year) {
    return cy.get('select#year_of_manufacture').should('be.visible').select(year)
  }

  getCarValue(carValue) {
    return cy.get('input#car_value_tier').should("be.visible").type(carValue)
  }

  getVehicleType(vehicleType) {
    return cy.get('select#vehicle_type_id').should("be.visible").select(vehicleType)
  }

  getSeatCapacity(seatCapacity) {
    return cy.get('input#seat_capacity').should("be.visible").type(seatCapacity)
  }

  getRegistrationId(registrationId) {
    return cy.get('select#emirate_of_registration_id').should("be.visible").select(registrationId)
  }

  getInsuranceType(InsuranceType) {
    return cy.get('select#car_type_insurance_id').should("be.visible").select(InsuranceType)
  }

  getCurrentInsurance(currentInsurance) {
    return cy.get('select#currently_insured_with').should("be.visible").select(currentInsurance)
  }

  getClaimHistory(claimHistory) {
    return cy.get('select#claim_history_id').should("be.visible").select(claimHistory)
  }

  getCreateButton() {
    return cy.get('button.btn.btn-warning.btn-sm')
      .contains('Create')
      .should('be.visible')
  }

  getSearchButton() {
    return cy.get("input#searchGenericSubmit").click()
  }

  getResetButton() {
    return cy.get('input#reset-btn-generic').should("be.visible").click()
  }

  getUpdateButton() {
    return cy.get('button#return_to_view').should("be.visible").click()
  }

  setLeadStatus() {
    return cy.get("select#leadStatus").should("be.visible").select("New Lead")
  }

  //Change lead status
  getChangeStatusButton() {
    return cy.get('button#lead-change-status-btn').should("be.visible").click()
  }

  getEditAssumptionsButtons() {
    return cy.get('button#edit-motor-assumptions-btn').should("be.visible").click()
  }

  getCylinderField() {
    return cy.get('input#cylinder').should("be.visible").click()
  }

  getAssumptionsUpdateButton() {
    return cy.get('button#update-motor-assumptions-btn').should("be.visible").click()
  }

  getVehivleModified() {
    return cy.get('select#is_modified').should("be.visible").select("No")
  }

  getBankFinanced() {
    return cy.get('select#is_bank_financed').should("be.visible").select("No")
  }

  getGCCStandard() {
    return cy.get("select#is_gcc_standard").should("be.visible").select("Yes")
  }

  // getInsuranceStatus(){
  //   return cy.get("select#current_insurance_status").should("be.visible").select("ACTIVE_TPL")
  // }

  getRegistrationYear() {
    return cy.get("select#year_of_first_registration").should("be.visible").select("2023")
  }


  //Send notes to customer
  getSendNotesToCustomerButton() {
    return cy.get("button#send-note-for-customer-btn").should("be.visible").click()
  }

  getNotesDescription() {
    return cy.get('textarea[id="txtDescription"]').should("be.visible").type("Test Description")
  }

  getSendNotesToCustomerSubmitButton() {
    return cy.get('button#send-notes-to-customer-btn').should("be.visible").click()
  }

  //Upload Documents
  getUploadDocButton(quoteID) {
    return cy.get(`a[href="https://crmstage.alfred.ae/quotes/car/${quoteID}/documents"]`).click()
  }

  getUploadFile() {
    return cy.get('#DL').attachFile('correct.xlsx')
  }

  getBackButton(quoteID) {
    return cy.get(`a[href="https://crmstage.alfred.ae/quotes/car/${quoteID}"]`).click()
  }

  //Add Activity
  getAddActivityButton() {
    return cy.get("button#add-activity-btn").should("be.visible").click()
  }

  assertTitle() {
    return cy.get("#exampleModalLabel").should("be.visible")
  }

  getTitleField() {
    return cy.get("input#email").should("be.visible").type("Test")
  }

  getDescriptionField(dummyData) {
    return cy.get("textarea#description").should("be.visible").type(dummyData)
  }

  getSelectAssigneeDropDown() {
    return cy.get("select#activity-assignee").should("be.visible").select("Test User - CAR_ADVISOR")
  }

  getModalSubmitButton() {
    return cy.get('button[type="submit"]').contains("Add Activity").click()
  }

  editActivityButton() {
    return cy.get('[onclick="activityEdit1(this)"]').should("be.visible").click()
  }

  editDescription() {
    return cy.get("#followup-div #description").should("be.visible").clear().type("Lorem Ipsum is simply dummy text")
  }

  getModalUpdateButton() {
    return cy.get('button[type="submit"]').contains("Update Activity").click()
  }

  deleteActivityButton() {
    return cy.get('[onclick="deleteActivity1(this)"]').should("be.visible").click()
  }


  // Add Additional Contact
  getAdditionalContactButton() {
    return cy.get('button#additional-contact-add-btn').should("be.visible").click()
  }

  getContactType() {
    return cy.get('select#additional_contact_type').should("be.visible").select("Mobile Number")
  }

  getContactValue() {
    return cy.get('input#additional_contact').should("be.visible").type("05012345678")
  }

  getAddContactSubmitButton() {
    return cy.get('button[id="additional-contact-modal-add-btn"]').should("be.visible").click()
  }

  // delete Additional Contact
  deleteContactButton() {
    return cy.get('.btn.btn-danger.btn-sm').contains('Delete').should("be.visible").click()
  }
  //Load History Button
  getLloadHistoryButton() {
    return cy.get('button#loadHistoryDataBtn').should("be.visible").click()
  }

  //View Audit Log
  getViewAuditLog() {
    return cy.get('button#auditablebtn').should("be.visible").click()
  }


  //Edit car quote plans
  getviewPlansButton(CDBID) {
    return cy.get(`a[plandetailurl="${CDBID}/plan_details/1"]`).eq(1).should('be.visible').click()
  }

  getSwitchButton() {
    return cy.get('input#is_manual_update').parent().find('.slider.round').click()
  }

  getInsurerQuoteNoField() {
    return cy.get('input[id="insurer_quote_no"]').should('be.visible').type('1000')
  }

  getActualPremiumField() {
    return cy.get('input[id="actual_premium"]').should('be.visible').type('500')
  }

  getCarValueField() {
    return cy.get('input[id="car_value"]').should('be.visible').type('1000')
  }

  getExcessField() {
    return cy.get('input[id="excess"]').should('be.visible').type('10')
  }

  getAncillaryExcess() {
    return cy.get('select[id="ancillary_excess"]').should('be.visible').select('1%')
  }

  getAddonsTab() {
    return cy.get('a[id="addons-tab"]').should('be.visible').click()
  }

  getAddonsByName(id, val) {
    cy.get('input[id="addon_price"]').eq(id).should('be.visible').clear().type(val)
  }

  getInclusionTab() {
    return cy.get('a[id="benefits-inclusion-tab"]').should('be.visible').click()
  }

  getExclusionsTab() {
    return cy.get('a[id="benefits-exclusion-tab"]').should('be.visible').click()
  }

  getRsaTab() {
    return cy.get('a[id="rsa-tab"]').should('be.visible').click()
  }

  getPolicyDetailsTab() {
    return cy.get('a[id="policy-detail-tab"]').should('be.visible').click()
  }

  getGeneralInfoTab() {
    return cy.get('a[id="general-tab"]').should('be.visible').click()
  }

  getAddonSubmitButton() {
    return cy.get('button[type="submit"]').contains('Update').should('be.visible').click()
  }

}

export default CarLeadPage;
