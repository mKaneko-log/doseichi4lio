window.addEventListener('load', function() {
    let backbutton = document.createElement('a');
    backbutton.textContent = '＜戻る';
    backbutton.setAttribute('href', '../');
    backbutton.setAttribute('id', 'backtoindex');
    // 追加
    document.body.after(backbutton);
});