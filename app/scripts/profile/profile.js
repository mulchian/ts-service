$(function () {
    $('#uploadProfilePic').on('change', function () {
        let path = $(this).val();
        if (path.length > 0) {
            let startIndex = Math.max(path.lastIndexOf('\\'), path.lastIndexOf('/')) + 1;
            $('#lblProfilePic').html(path.substring(startIndex));
        } else {
            $('#lblProfilePic').html('Auswählen');
        }
    })
})


function addAsFriend() {

}

function goToFriendly() {
    let opponent = $('#teamname').val();
    location.replace('/index.php?site=buero&do=friendly&opponent=' + opponent);
}