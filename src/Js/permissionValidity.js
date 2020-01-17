export default function permissionSatisfied(permission, permissions) {
    var match = false;

    permissions.forEach(function (item) {
        // Straight match
        if(item === permission){
            match = true;
            return true;
        }

        var permissionWithoutWildcard = item;

        if(item.includes('.*')){
            permissionWithoutWildcard = item.replace('.*', '');
        }

        // e.g. permission is auth.user.create, and item is auth.user.*
        if(permission.startsWith(permissionWithoutWildcard)){
            match = true;
            return true;
        }

        // e.g. permission is auth.user, and item is auth.user.add
        if(item.includes(permission)){
            match = true;
            return true;
        }

    });

    return !!match;
}