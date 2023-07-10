// ---
class Ajax {
    #toFormData(values) {
        const f = new FormData();
        for(const k in values){ f.append(k, values[k]); }
        return f;
    }
    #request(m, u, v) {
        return new Request(u, {
            method: m,
            body: this.#toFormData(v),
            cache: 'no-cache'
        });
    }
    post(url, values) {
        return fetch(this.#request('POST', url, values));
    }
}
const axon = new Ajax();

(function() {
    u('form#login_form').handle('submit', function(e) {
        // ログイン
        let val = {};
        u(this).find('input, button, textarea').each((node, i) => {
            let id = u(node).attr('name');
            if(id !== null) { val[id] = node.value; }
        });
        val['uagent'] = navigator.userAgent;
        console.log(val);
        axon.post('./_api/index.php', val).then(req => req.text())
            .then(result => console.log(result));
    });
    u('form#send_message').handle('submit', function(e) {
        // 送信
    });
})();