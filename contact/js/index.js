class Storage {
    #_key;
    #_data;
    #Save() {
        sessionStorage.setItem(this.#_key, JSON.stringify(this.#_data));
    }

    constructor(key) {
        this.#_key = key;
        this.#_data = JSON.parse(sessionStorage.getItem(key) || '{}');
    }
    set setSerializeArray(vals) {
        // [{name: 'input[name]', value: 'input[value]'}, ...] をほぐす
        let result = {};
        $.each(vals, (i, v) => {
            result[v.name] = v.value;
        });
        this.#_data = Object.assign({}, this.#_data, result);
        this.#Save();
    }
    get getAll() { return this.#_data; }
    get getJson() { return JSON.stringify(this.#_data); }

    getItem(key) { return this.#_data[key]; }
    setItem(key, value) {
        let result = {};
        result[key] = value;
        this.#_data = Object.assign({}, this.#_data, result);
        this.#Save();
    }
    removeItem(key) {
        if(Object.keys(this.#_data).indexOf(key) >= 0) {
            delete this.#_data[key];
            this.#Save();
        }
    }
}
const hako = new Storage('contact_input');

// jQueryで addEventLister('load')
$(window).on('load', function() {
    $.ajaxSetup({
        method: 'POST',
        url: './contact.php',
        global: false,
        cache: false
    });
    // submitで処理分け
    $('form').on('submit', function(e) {
        e.preventDefault();
        //console.log($(e.currentTarget).serializeArray());
        let _doing = $(e.originalEvent.submitter).data('trigger');
        hako.setItem('doing', _doing);
        //console.log(_doing);
        if(_doing == 'submit') {
            // 送信
            if($('#form_confirm_ok').is(':checked')) {
                //alert('submitted!');
                $(e.originalEvent.submitter).prop('disabled', true);
                $.ajax({data: {val: hako.getJson}}).then(
                    function(val) {
                        // 返答あり
                        //console.log(val);
                        let result = JSON.parse(val);
                        alert(result['message']);
                    }, function(err) {
                        console.log(err);
                        alert('なんかエラーです');
                    }
                );
            }
            // -->| 送信
        } else if(_doing == 'confirm') {
            // 確認
            hako.setSerializeArray = $(e.currentTarget).serializeArray();
            //console.log(hako.getJson);
            $.ajax({data: {val: hako.getJson}}).then(
                function(val) {
                    let deferred = $.Deferred();
                    let result = JSON.parse(val);
                    //console.log(val);
                    if(Object.keys(result).indexOf('invalid') >= 0) {
                        // PHPでのメール書式エラー
                        alert('メールアドレスが認識されませんでした');
                        return deferred.reject();
                    }
                    return deferred.resolve(result);
                },
                function(err) {
                    console.log(err);
                    alert('なんかエラーです');
                }
            ).done(function(val) {
                // セッション保持？
                let stoKeys = Object.keys(hako.getAll);
                Object.keys(val).forEach(v => {
                    if(v.indexOf('input') < 0) { hako.setItem(v, val[v]); }
                });
                hako.removeItem('doing');
                // 確認画面へ切り替え
                $('#form_confirm_name').text(hako.getItem('input_name'));
                $('#form_confirm_email').text(hako.getItem('input_email'));
                $('#form_confirm_text').text(hako.getItem('input_text'));
                if($('#form_confirm_ok').is(':checked')) {
                    $('#form_confirm_ok').prop('checked', false);
                }
                $('form > div[id^="form_"]').toggle();
            });
            // -->| 確認
        }
        // -->| submit()
    });

    $('#confirm_button_back').on('click', function(e) {
        e.preventDefault();
        $('form > div[id^="form_"]').toggle();
        $('input[type="submit"]:disabled').prop('disabled', false);
    });
});