function positionSorter(a, b) {
    let positions = ['QB', 'RB', 'FB', 'WR', 'TE', 'C', 'OG', 'OT', 'DT', 'DE', 'MLB', 'OLB', 'CB', 'FS', 'SS', 'K', 'P'];
    let aaKey = positions.indexOf(a);
    let bbKey = positions.indexOf(b);
    return aaKey - bbKey;
}
function intensitySorter(a, b) {
    let aaKey = a.substring(7,8);
    let bbKey = b.substring(7,8);
    return aaKey - bbKey;
}