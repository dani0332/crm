import AdminUserPage from '../../../pageObjects/AdminUserPage';
import CommonPage from '../../../pageObjects/CommonPage';
let userData = require('../../../fixtures/adminUserData.json')
describe('Group Mediacl Qoutes', () => {
    const commonPage = new CommonPage()
    const userPage = new AdminUserPage()

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

    it.only('Should Search User', () => {
        cy.visit('/admin/users')
        commonPage.verifyURL('/admin/users')
        //Search User
        userPage.getEmailField('im.automation4@gmail.com')
        userPage.getResetButton()
        userPage.getSearchNameField('Im.Automation.Testing')
        userPage.getResetButton()
    })

    it('Should Create user', () => {
        //Create User
        userPage.getCreateButton()
        commonPage.getPanelHeadingAssertion('Create User')
        userPage.getNameField().type(usersData.adminUserData.name)
        commonPage.getEmail('abdullah+8as@gmail.com')
        commonPage.getMobileNumber(usersData.adminUserData.mobileNo)
        userPage.getPasswordField(usersData.adminUserData.password)
        userPage.getField('0', 'ADMIN')
        userPage.getField('1', 'CAR')
        userPage.getField('2', 'Test')
        userPage.getField('3', 'CAR')
        commonPage.getButtonByName('Create')
        commonPage.getPopUpAssertion('User has been stored')

        // Edit User
        userPage.getEditButton()
        userPage.getNameField().type(usersData.adminUserData.name)
        commonPage.getEmail('abdullah+5as@gmail.com')
        commonPage.getMobileNumber(usersData.adminUserData.mobileNo)
        userPage.getPasswordField(usersData.adminUserData.password)
        userPage.getField('0', 'Advisor')
        userPage.getField('1', 'Health')
        userPage.getField('2', 'EBP')
        userPage.getField('3', 'Health')
        userPage.getField('4', 'Faisal Abbas - CAR_MANAGER')
        commonPage.getButtonByName('Update')
        commonPage.getPopUpAssertion('User has been updated')
    })
})
