var assert = require('assert');

import Service from '../../src/Js/permissionValidity'

var permissionsHas = [
    'auth.permissions.view',
    'auth.users.*'
];

describe('Permission Validity Service Test', function() {
    it('should determine permission met', function() {
        assert.equal(Service('auth.users.create', permissionsHas), true);
        assert.equal(Service('auth.users.modify', permissionsHas), true);
        assert.equal(Service('auth.users.*', permissionsHas), true);
        assert.equal(Service('auth.users', permissionsHas), true);
        assert.equal(Service('auth.permissions', permissionsHas), true);
        assert.equal(Service('auth.permissions.view', permissionsHas), true);
    });

    it('should determine permission not met', function() {
        assert.equal(Service('auth.members.create', permissionsHas), false);
        assert.equal(Service('auth.permissions.alter', permissionsHas), false);
    });
});