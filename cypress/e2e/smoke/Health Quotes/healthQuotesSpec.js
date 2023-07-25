import CommonPage from '../../../pageObjects/CommonPage';
import HealthLeadPage from '../../../pageObjects/HealthLeadPage';
let quoteData = require('../../../fixtures/qoutesData.json')
let healthLeadData = require('../../../fixtures/healthLeadData.json')
let leadUrl
let quoteId
let availablePlans
describe('Health qoutes', () => {

  const commonPage = new CommonPage()
  const healthLeadPage = new HealthLeadPage()

  beforeEach(() => {
    cy.loginByCookies()
    cy.runRoutes()
  })
  const resizeObserverLoopErrRe = /^[^(ResizeObserver loop limit exceeded)]/
  Cypress.on('uncaught:exception', (err) => {
    /* returning false here prevents Cypress from failing the test */
    if (resizeObserverLoopErrRe.test(err.message)) {
      return false
    }
  })

  it('Should create lead car qoutes insurance', () => {
    Cypress.on('uncaught:exception', err => {
      if (resizeObserverLoopErrRe.test(err.message)) {
        return false
      }
    })
    cy.visit('/quotes/health/create')
    commonPage.verifyURL('/quotes/health/create')
    commonPage.getTextFieldByName('FIRST NAME', quoteData.personalInfo.firstName)
    commonPage.getTextFieldByName('LAST NAME ', quoteData.personalInfo.lastName)
    healthLeadPage.getEmailField(quoteData.personalInfo.email)
    healthLeadPage.getMobileNumberField(quoteData.personalInfo.phoneNumber)
    commonPage.getDateTimeDropdown()
    healthLeadPage.getYearModal()
    healthLeadPage.getYear()
    healthLeadPage.getDate()
    commonPage.getTextFieldByName('POLICY NUMBER ', healthLeadData.healthData.policyNumber)
    commonPage.getDropdownByName('WHO WOULD YOU LIKE COVER FOR? ', 'Individual')
    commonPage.getNationality('NATIONALITY ', 'Afghan')
    commonPage.getDropdownByName('EMIRATE OF YOUR VISA ', 'Dubai')
    commonPage.getTextFieldByName('PREFERENCE ', healthLeadData.healthData.reference)
    commonPage.getTextFieldByName('DETAILS ', healthLeadData.healthData.details)
    commonPage.getDropdownByName('LEAD TYPE ', 'Individual')
    commonPage.getDropdownByName('CURRENTLY INSURED WITH ', 'Abu Dhabi National Takaful')
    commonPage.getDropdownByName('MARITAL STATUS ', 'Single')
    commonPage.getDropdownByName('MEMBER CATEGORY ', 'Employee')
    commonPage.getDropdownByName('SALARY BAND ', 'More than AED 4000')
    commonPage.getDropdownByName('GENDER ', 'Male')
    healthLeadPage.getSubmitButton()
    commonPage.verifyURL('/quotes/health')
    cy.wait(10000)
    cy.url().then(data => {
      cy.log("Data logged", data)
      leadUrl = `/${data.substring(data.lastIndexOf('quotes/health/'))}`
      quoteId = `${(data.substring(data.lastIndexOf('/'))).replace('/', '')}`
      cy.log(leadUrl)
      cy.log(quoteId)
    })
  })

  it('Should Update Quote Details', () => {
    cy.visit('/quotes/health')
    commonPage.verifyURL('/quotes/health')
    healthLeadPage.getTextFieldByName('CDB ID', `HEA-${quoteId}`)
    healthLeadPage.getSearchButton()
    healthLeadPage.getOpenQuoteButton(quoteId)
    healthLeadPage.getEditButton()
    healthLeadPage.getHealthTextFieldByName("FIRST NAME ", "Muhammad Updated")
    healthLeadPage.getHealthTextFieldByName("LAST NAME", "Abdullah Updated")
    healthLeadPage.getHealthTextFieldByName("DETAILS", "Test Details")
    healthLeadPage.getNationalityDropdown("NATIONALITY")
    healthLeadPage.getNationalityValue("Pakistani")
    healthLeadPage.getHealthDetailDropdown("EMIRATE OF YOUR VISA")
    healthLeadPage.getEmirateOfVisaValue()
    healthLeadPage.getMemberCategoryDropdown("MEMBER CATEGORY")
    healthLeadPage.getMemberCategoryValue()
    healthLeadPage.getSubmitButton()
  })

  it('Should Add member', () => {
    cy.visit(`${leadUrl}`)
    commonPage.verifyURL(`/quotes/health/`)
    //Add Member
    healthLeadPage.getAddMemberButton()
    healthLeadPage.getAddMemberDropdown("Emirate of Visa")
    healthLeadPage.getAddMemberDropdownVisa("Dubai")
    healthLeadPage.getAddMemberDropdown("Gender")
    healthLeadPage.getAddMemberDropdownVisa("Male")
    healthLeadPage.getAddMemberDOB("DOB")
    healthLeadPage.getDate()
    healthLeadPage.getAddMemberCategoryDropdown("Member Category")
    healthLeadPage.getAddMemberDropdowns('Employee')
    healthLeadPage.getSubmitButton()
    //Delete Member 
    healthLeadPage.getDeleteButton()
    healthLeadPage.getModalDeleteButton()
    cy.contains(' Available Plans ').parent().within(() => {
      cy.get('span.x-tag').should('be.visible').then((data) => {
        availablePlans = parseInt(data.text());
        cy.log(availablePlans)
      })
    })
    if (availablePlans > 0) {
      healthLeadPage.getViewPlansButton() //View Plans Button

      //General Info Tab
      healthLeadPage.getTabMembersAssertion('Provider Name')
      healthLeadPage.getTabMembersAssertion('Network Provider')
      healthLeadPage.getTabMembersAssertion('Base Premium')
      healthLeadPage.getTabMembersAssertion('Basmah')
      healthLeadPage.getTabMembersAssertion('Policy Fee')
      healthLeadPage.getTabMembersAssertion('Total (exclusive of VAT)')

      //Hide Plan toggle button
      healthLeadPage.getToggleButton()

      //Members Tab
      healthLeadPage.getMemberstab()
      healthLeadPage.getMemberAsertions('Relationship')
      healthLeadPage.getMemberAsertions('DOB')
      healthLeadPage.getMemberAsertions('Gender')
      healthLeadPage.getMemberAsertions('Premium')

      //In Patient Tab
      healthLeadPage.getInPatientTab()
      healthLeadPage.getInPatientAssertions('Pre-existing & Chronic Conditions*')
      healthLeadPage.getInPatientAssertions('Surgery and Recovery*')
      healthLeadPage.getInPatientAssertions('Network Provider (TPA)')
      healthLeadPage.getInPatientAssertions('Room and board')

      //Out Patient Tab
      healthLeadPage.getOutPatientTab()
      healthLeadPage.getInPatientAssertions('Pre-existing & Chronic Conditions*')
      healthLeadPage.getInPatientAssertions('Medicines*')
      healthLeadPage.getInPatientAssertions('Alternative Medicine')
      healthLeadPage.getInPatientAssertions('Network Provider')
      healthLeadPage.getInPatientAssertions('Consultation & Diagnostics*')
      healthLeadPage.getInPatientAssertions('Physiotherapy*')
      healthLeadPage.getInPatientAssertions('Routine Dental*')
      healthLeadPage.getInPatientAssertions('Routine Optical*')

      //Region coverage & Network list
      healthLeadPage.getRegionCoverageTab()
      healthLeadPage.getInPatientAssertions('Regions Covered')
      healthLeadPage.getInPatientAssertions('Direct Billing Network (Outpatient)')
      healthLeadPage.getInPatientAssertions('Direct Billing Network (Inpatient)')

      //Co-pay/Co-insurance
      healthLeadPage.getCopayCoInsuranceTab()
      healthLeadPage.getInPatientAssertions('Outpatient Consultation*')
      healthLeadPage.getInPatientAssertions('Outpatient Diagnostics*')
      healthLeadPage.getInPatientAssertions('Inpatient*')
      healthLeadPage.getInPatientAssertions('Outpatient Physiotherapy*')
      healthLeadPage.getInPatientAssertions('Outpatient Medicine*')
      healthLeadPage.getInPatientAssertions('Routine Dental*')
      healthLeadPage.getInPatientAssertions('Routine Optical*')

      //Maternity cover
      healthLeadPage.getMaternityTab()
      healthLeadPage.getInPatientAssertions('Outpatient Maternity Co-insurance')
      healthLeadPage.getInPatientAssertions('Outpatient Maternity')
      healthLeadPage.getInPatientAssertions('Newborn')
      healthLeadPage.getInPatientAssertions('Delivery')
      healthLeadPage.getInPatientAssertions('Important Note')

      //getGeneralInfoTab
      healthLeadPage.getGeneralTab()
      healthLeadPage.getToggleButton()

      //Close button
      healthLeadPage.getCloseButton()
    }
    else {

      //Add Additional Contact
      healthLeadPage.getAddAdditionalContactButton()
      healthLeadPage.getLeadStatusDropdown("Type")
      healthLeadPage.selectMobileNumber()
      healthLeadPage.getAddAdditionalContactvalueField()
      healthLeadPage.getAdditionalContactSubmitButton()
      healthLeadPage.getButtonByName(" Load History Data ")
      healthLeadPage.getButtonByName(" Load Audit Logs ")
    }

  })
})