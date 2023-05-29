import AdminRolesPage from '../../../pageObjects/AdminRolesPage';
import CommonPage from '../../../pageObjects/CommonPage';
let rolesData = require('../../../fixtures/adminRolesData.json')
let id
describe('Group Mediacl Qoutes', () => {
    const commonPage = new CommonPage()
    const rolePage = new AdminRolesPage()

    beforeEach(() => {
        cy.loginByCookies(Cypress.env('imcrm_session'), Cypress.env('XSRF-TOKEN'))
        cy.runRoutes()
    })
    const resizeObserverLoopErrRe = /^[^(ResizeObserver loop limit exceeded)]/
    Cypress.on('uncaught:exception', (err) => {
        /* returning false here prevents Cypress from failing the test */
        if (resizeObserverLoopErrRe.test(err.message)) {
            return false
        }
    })

    it('Should Creeate Role', () => {
        cy.visit('/admin/roles')
        commonPage.verifyURL('/admin/roles')

        //Create Role
        rolePage.getCreateRoleButton()
        rolePage.getNameField(rolesData.adminRolesData.name)
        rolePage.getPermissionField('role-list')
        rolePage.getCreateButton()
        commonPage.getPopUpAssertion('Role has been stored')
        cy.url().then(data => {
            id = `${(data.substring(data.lastIndexOf('/'))).replace('/', '')}`
            cy.log(id)
        })

    })
    it('Should Search and Update role', () => {
        cy.visit('/admin/roles')
        commonPage.verifyURL('/admin/roles')
        rolePage.getSearchField()
        rolePage.openRoleById(id)

        //Edit Role Details
        rolePage.getEditButton(id)
        rolePage.getNameField(rolesData.adminRolesData.name + " Updated")
        rolePage.getRemoveButton()
        rolePage.getEditPermission()
        rolePage.getUpdate()
        commonPage.getPopUpAssertion('Role has been updated')
        rolePage.getViewAuditLogsButton()
        commonPage.getHeadingAssertion('Audit Logs')


    })
})