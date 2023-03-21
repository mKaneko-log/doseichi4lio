const nm = 'TODOlist';

class TodoDOM {
  #_$todo_item;

  /**
   * [PRIVATE] ID番号を書き換えた新規DOM生成
   * @param {*} id 
   * @returns Umbrella JS Object
   */
  #ReplaceId(id) {
    let $todo_item = this.#_$todo_item.clone();
    $todo_item.removeClass('d-none').attr('id', "todo-item-"+ id.toString())
      .find('[id*="todo-item-00"]').each(function(node, i) {
        let _newId = u(node).attr('id').toString().replace('00', id.toString());
        u(node).attr('id', _newId);
      });
    $todo_item.find('input[type="checkbox"]').attr('value', id);
    return $todo_item;
  }

  constructor() {
    this.#_$todo_item = u('#todo-item-00');
  }

  /**
   * TODOデータからDOMを生成する
   * @param {Object} data 
   * @returns Umbrella JS Object
   */
  create(data) {
    let $todo_item = this.#ReplaceId(data.id);
    $todo_item.find('[id$="-id"]').text(data.id);
    $todo_item.find('[id$="-text"]').text(_.unescape(data.title));
    $todo_item.find('input[type="checkbox"]').data('finished', data.finished);
    $todo_item.find('button.btn-octicon-danger').data('id', data.id);
    return $todo_item;
  }
}
var dom = new TodoDOM();

class TodoStorage {
    #_storage;
    #_data;
    #_next;

    /**
     * [PRIVATE] localStorageに保存
     */
    #SaveToStorage() {
      return new Promise((resolve, reject) => {
        let savedata = Object.assign({}, {data: this.#_data}, {hoge: 'fuga'});
        localStorage.setItem(nm, JSON.stringify(savedata));
        resolve(1);
      });
    }
    /**
     * [PRIVATE] IDを指定して取得
     * @param {*} id 
     * @returns Object
     */
    #GetToID(id) {
      return _.findWhere(this.#_data, {id: id});
    }

    constructor() {
        // localStorageから以前のデータを取得
        this.#_storage = JSON.parse(localStorage.getItem(nm) || '{}');
        this.#_data = this.#_storage.data || [];
        this.#_next = 1;
        if(0 < this.#_data.length) {
          let n = _.max(this.#_data, function(o) { return o.id; });
          this.#_next = parseInt(n.id.toString()) + 1;
        }
    }

    /**
     * 全データを取得
     */
    get getAll() {
      return this.#_data;
    }
    /**
     * 最後のデータを取得
     */
    get getNewer() {
      return this.#GetToID(this.#_next - 1);
    }

    /**
     * 新規追加
     */
    async add(text) {
      let val = {title: _.escape(text)};
      val = Object.assign(val, {id: this.#_next, finished: false});
      this.#_data.push(val);
      return this.#SaveToStorage().then((ret) => {this.#_next += ret}).then((ret) => {console.log('data added.')});
    }
    /**
     * 終了を切り替える
     */
    toggleFinished(id) {
      let val = this.#GetToID(id);
      val.finished = !(val.finished);
      this.#SaveToStorage().then((ret) => {console.log(id.toString() +' toggled.')});
    }
    /**
     * 削除
     * @param {*} id 
     */
    async remove(id) {
      this.#_data = _.reject(this.#_data, function(item) { return (item.id == id); });
      return this.#SaveToStorage().then((ret) => {console.log(id.toString() +' deleted.')})
        .then((ret) => { if(this.#_data.length == 0) { this.#_next = 1; } }); // 空になったらIDリセット
    }
}
var hako = new TodoStorage();

/***
  こんな感じに？
  {
    data: [
        {id: 1, title: 'おつかい', finished: false},
        {id: 2, title: 'しゅくだい', finished: true},
        {id: 3, title: 'かいらんばん', finished: true}
    ],
    hoge: 'fuga'  ←未使用
  }
***/

window.addEventListener('load', () => {
  hako.getAll.forEach(data => {
    u('main').append(dom.create(data));
  });

  // -----
  u('button#todo-add-button').handle('click', function(e) {
    let text = u('input#todo-add-text').first().value || "";
    if(0 < text.length) {
      hako.add(text).then(e => {
        u('main').append(dom.create(hako.getNewer));
      });
      u('input#todo-add-text').first().value = "";
    }
  });
  u('form#todo-add').handle('submit', function(e) {
    // alert('submit!');
    u('button#todo-add-button').trigger('click');
  });
// -----
  u('input[type="checkbox"].v-hidden').handle('change', function(e) {
    let _finished = Boolean(u(this).data('finished'));
    u(this).data('finished', !_finished);
    let $checkIcon = u(this).closest('label').children('span.color-fg-success');
    $checkIcon.toggleClass('v-hidden');
  });
  u('span.position-absolute').handle('click', function(e) {
    let $checkbox = u(this).closest('label').children('input[type="checkbox"]');
    $checkbox.trigger('change');
    hako.toggleFinished(Number($checkbox.attr('value')));
  });
  u('button.btn-octicon-danger').handle('click', function(e) {
    let text = u(this).closest('div[id^="todo-item"]').find('div[id$="-text"]').text();
    if(confirm('「'+ text +'」を削除します。')) {
      let id = Number(u(this).data('id'));
      hako.remove(id).then(e => {
        u('div#todo-item-'+ id.toString()).remove();
      });
    }
  });
  // -----

  u('input[type="checkbox"]').each(function(node, i) {
    if(u(node).data('finished')) {
      u(node).trigger('change');
    }
  });

});