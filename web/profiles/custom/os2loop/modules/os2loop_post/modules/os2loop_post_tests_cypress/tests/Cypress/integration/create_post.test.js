// /*
//   @see https://sevaa.com/blog/2018/10/end-to-end-testing-with-drupal-and-cypress/
//   @see https://www.drupal.org/project/cypress
//   @see https://github.com/AmazeeLabs/cypress
// */

// @see https://medium.com/@nickdenardis/getting-cypress-js-to-interact-with-ckeditor-f46eec01132f
Cypress.Commands.add("type_ckeditor", (element, content) => {
  cy.window()
    .then(win => {
      win.CKEDITOR.instances[element].setData(content);
    });
});

describe('Create post', () => {
  // Install OS2Loop before running tests.
  before(() => {
    cy.drupalInstall({
      profile: 'os2loop',
      config: '../config/sync'
    })
    cy.drush('--yes pm:enable os2loop_post_fixtures')
    cy.drush('--yes content-fixtures:load')
  })

  it('Can sign in as os2loop_post_user', () => {
    cy.visit('/user')

    cy.get('[name=name]').type('os2loop_post_user')
    cy.get('[name=pass]').type('os2loop_post_user-password')
    cy.get('[value="Log in"]').click()

    cy.contains('Member for')
  })

  it('Can check user profile', () => {
    cy.drupalSession({user: 'os2loop_post_user'});
    // cy.drupalSession({toolbar: true});

    cy.visit('/user')

    cy.contains('Member for')
  })

  it('Can create content of type post', () => {
    cy.drupalSession({user: 'os2loop_post_user'});
    // cy.drupalSession({toolbar: true});

    cy.visit('/admin/content')

    cy.contains('Add content').click()
    cy.contains('Post').click()

    // cy.scrollTo('top')
    cy.get('[id="edit-title-0-value"]')
    // .scrollIntoView({offset: { top: 100, left: 0 }})
      .type('My first post', {force: true})

    cy.type_ckeditor('edit-os2loop-post-content-0-value', `
<p>Read all about it here:</p>

<ol>
<li>one</li>
<li>two</li>
<li>three</li>
</ol>
`)

    cy.get('[name="os2loop_shared_subject[0][target_id]"]').type('dok', {force: true})
    cy.contains('Dokumentation').click({force: true})

    cy.get('[name="os2loop_shared_profession[0][target_id]"]').type('teknisk', {force: true})
    cy.contains('Teknisk servicemedarbejder/pedel/håndværker').click({force: true})

    cy.contains('Save').click()
  })

  it('Can edit content of type post', () => {
    cy.drupalSession({user: 'os2loop_post_user'});

    cy.visit('/admin/content')

    cy.contains('My first post').click()
    cy.contains(/^Edit$/).click({force: true})

    // cy.scrollTo('top')
    // cy.get('[id="edit-title-0-value"]')
    // // .scrollIntoView({offset: { top: 100, left: 0 }})
    //   .type('My first post', {force: true})

    cy.type_ckeditor('edit-os2loop-post-content-0-value', `
<p><strong>Updated</strong> Read all about it here:</p>

<ol>
<li>one</li>
<li>two</li>
<li>three</li>
<li>four</li>
</ol>
`)

    cy.contains('Save').click()
  })
})
