class AdminUserPage {
    //Search User
    getEmailField(email) {
        return cy.get('input#users_email').should('be.visible').type(email).type('{enter}')
    }

    getResetButton() {
        return cy.get('input[type="reset"]').should('be.visible').click()
    }

    getSearchNameField(name) {
        return cy.get('input#users_name').should('be.visible').type(name).type('{enter}')
    }



    //Create User
    getCreateButton() {
        return cy.get('a[href="https://crmstage.alfred.ae/admin/users/create"]').should('be.visible').click()
    }

    getNameField() {
        return cy.get('input#name').should('be.visible').clear()
    }

    getPasswordField(password) {
        return cy.get('input#password').should('be.visible').clear().type(password)
    }

    getField(eq, value) {
        return cy.get('input.select2-search__field').should('be.visible').eq(eq).type(value).type('{enter}')
    }

    getEditButton() {
        return cy.get('a.btn.btn-warning.btn-sm').contains('Edit').should('be.visible').click()
    }
}
export default AdminUserPage;