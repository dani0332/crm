class CommonPage{

    verifyURL(endPoint){
      return cy.url().should('include',endPoint)
    }

    getApiIntercept(interceptName,responseCode){
      cy.wait(interceptName).its('response.statusCode').should('eq', responseCode);
    }

    getFirstNameField(first_name){
      return cy.get('input[name="first_name"]').should('be.visible').clear().type(first_name)
    }

    getLastNameField(last_name){
      return cy.get('input#last_name').should('be.visible').clear().type(last_name)
    }

    //Get date of birth
    getDOB(month,year,day){
      cy.get('input#dob').click()
      cy.get('select.ui-datepicker-month').should('be.visible').select(month)
      cy.get('select.ui-datepicker-year').should('be.visible').select(year)
      cy.get('[data-handler="selectDay"]').should('be.visible').eq(day).click()
    }

    getMobileNumber(phoneNumber){
      return cy.get("input#mobile_no").should('be.visible').type(phoneNumber)
    }

    getEmail(email){
     return cy.get('input#email').type(email)
    }

    getNationalityId(nationality){
      return cy.get('select#nationality_id').should('be.visible').select(nationality)
    }

    getSuccessAssertion(){
      return cy.get("div#vehicle_assumptions_success_msg").should("have.text","Vehicle Assumptions Data Found")
    }
    
    getEditButton(){
      return cy.get('a#texta').should("be.visible").click()
    }
  }
  export default CommonPage;
  