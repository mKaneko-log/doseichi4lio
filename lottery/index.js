
window.addEventListener('load', () => {
    u('#form1').handle('submit', function(e) {
        const toNumberNode = function(node) { return Number(node.value); }
        const toggleDisabled = function(node, isDisabled) { node.disabled = isDisabled; }
        // フォーム内容の無効化
        let $inputs = u(this).find('input, button');
        $inputs.each(node => toggleDisabled(node, true));
        // 選択された削除数字の取得
        let $chk_delNums = u(this).find('input[name="delnum"]:checked').array(node => node);
        var del_nums = [];
        if(0 < $chk_delNums.length) {
            del_nums = $chk_delNums.map(toNumberNode);
        }
        console.log(del_nums);

        let $chk_reqOdds = u(this).find('input[name="odds"]:checked').array(node => node);
        // _.sample(arr) で総奇数数選択のランダム取得／選択無しの処理
        var req_odd = -1;
        if(0 < $chk_reqOdds.length) {
            req_odd = _.sample($chk_reqOdds.map(toNumberNode));
        }
        console.log(req_odd);

        // ルーレット開始
        // TODO: その他にも対応（設定の変数化）
        let result = [];
        // シード値を都度設定する
        const dt = Date.now();
        let seed = dt.valueOf();
        var mt = new MersenneTwister(seed);
        var count_odd = 0;
        console.log('>----------');
        var count_lotte = 0;
        do {
            let num = mt.nextInt(1, 44);
            // 削除数字に該当しない
            if(del_nums.indexOf(num) < 0) {
                // 総奇数設定
                if(req_odd < 0) {
                    // 設定なし
                    result.push(num);
                    result = _.uniq(result);
                } else {
                    // 設定あり
                    if(num % 2 == 0) {
                        // 偶数
                        if(req_odd <= count_odd) {
                            // 総奇数数を超えている
                            result.push(num);
                            result = _.uniq(result);
                        }
                    } else {
                        // 奇数
                        if(req_odd <= count_odd) { continue; }
                        // 総奇数数を超えていない
                        count_odd++;
                        result.push(num);
                        result = _.uniq(result);
                    }
                }
            }
            // エラー用カウンタ、50回を超えたら強制終了
            count_lotte++;
            if(50 < count_lotte) { break; }
        } while(result.length < 6);
        // 数値としてソートするにはFunctionを入れる
        result.sort((a, b) => a - b);
        console.log(result);
        // 結果出力
        let $result = u('#list-result');
        let $res_node = u('<li>');
        $result.empty();
        if(result.length < 6) {
            // エラー
            $result.append($res_node.text('取得できませんでした'));
        } else {
            // 結果
            result.forEach((item, i) => {
                let $li = $res_node.clone().addClass('res-node');
                $result.append($li.text(item));
            });
            $result.append($res_node.text('奇数の数：' + ((req_odd < 0)? '指定なし': req_odd)));
        }
        console.log('---------->|');
        // フォーム内容の有効化
        $inputs.each(node => toggleDisabled(node, null));
    });
});
