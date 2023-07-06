class AdminRolesPage {
    //Create Role
    getCreateRoleButton() {
        return cy.get('a.btn-sm').contains('Create Role').should('be.visible').click()
    }

    getNameField(name) {
        return cy.get('input#name').should('be.visible').clear().type(name)
    }

    getPermissionField(role) {
        return cy.get('input[type="search"]').should('be.visible').type(role).type('{enter}')
    }
    getCreateButton() {
        return cy.get('button#return_to_view').should('be.visible').click()
    }
    // Edit role
    getSearchField(name) {
        return cy.get('input[type="search"]').should('be.visible').type('muhammad16')
    }
    openRoleById(id) {
        return cy.get(`a[href*="admin/roles/${id}"]`).should('be.visible').click()
    }
    getEditButton(id) {
        return cy.get(`a[href*="admin/roles/${id}/edit"]`).contains('Edit').should('be.visible').click()
    }

    getEditPermission() {
        return cy.get('input[type="search"]').should('be.visible').type('activities-list').type('{enter}')
    }

    getRemoveButton() {
        return cy.get('span.select2-selection__choice__remove').should('be.visible').eq(0).click()
    }

    getViewAuditLogsButton() {
        return cy.get('button#auditablebtn').contains('View Audit Logs').should('be.visible').click()
    }

    getUpdate() {
        return cy.get('button#return_to_view').should('be.visible').click()
    }

    //Delete Role
    getDeleteButton() {
        return cy.get('a[href="#"]').contains('Delete').should('be.visible').click()
    }

    getConfirmButton() {
        return cy.get('h5[id="exampleModalLabel"]').contains('Confirmation').should('be.visible')
    }

    getDeleteAssertion() {
        return cy.get('div.alert.alert-danger').contains('Role has been deleted').should('be.visible')
    }


}
export default AdminRolesPage;