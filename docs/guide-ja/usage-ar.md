�A�N�e�B�u���R�[�h���g��
========================

Yii �̃A�N�e�B�u���R�[�h�̎g�p��@�Ɋւ����ʓI�ȏ��ɂ��ẮA[�K�C�h](https://github.com/yiisoft/yii2/blob/master/docs/guide/db-active-record.md) ���Q�Ƃ��Ă��������B

Elasticsearch �̃A�N�e�B�u���R�[�h���`���邽�߂ɂ́A���Ȃ��̃��R�[�h�N���X�� [[yii\elasticsearch\ActiveRecord]] ����g�����āA�Œ���A���R�[�h�̑������`���邽�߂� [[yii\elasticsearch\ActiveRecord::attributes()|attributes()]] ���\�b�h����������K�v������܂��B
Elasticsearch �ł̓v���C�}���L�[�̈������ʏ�ƈقȂ�܂��B
�Ƃ����̂́A�v���C�}���L�[ (elasticsearch �̗p��ł� `_id` �t�B�[���h) ���A�f�t�H���g�ł͑����̂����ɓ��Ȃ�����ł��B
�������A`_id` �t�B�[���h�𑮐��Ɋ܂߂邽�߂� [�p�X�}�b�s���O](http://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-id-field.html) ���`���邱�Ƃ͏o���܂��B
�p�X�}�b�s���O�̒�`�̎d��ɂ��ẮA[elasticsearch �̃h�L�������g](http://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-id-field.html) ���Q�Ƃ��Ă��������B
document �܂��� record �� `_id` �t�B�[���h�́A[[yii\elasticsearch\ActiveRecord::getPrimaryKey()|getPrimaryKey()]] ����� [[yii\elasticsearch\ActiveRecord::setPrimaryKey()|setPrimaryKey()]] ���g���ăA�N�Z�X���邱�Ƃ��o���܂��B
�p�X�}�b�s���O����`����Ă���ꍇ�́A[[yii\elasticsearch\ActiveRecord::primaryKey()|primaryKey()]] ���\�b�h���g���đ����̖��O���`���邱�Ƃ��o���܂��B

�ȉ��� `Customer` �ƌĂ΂�郂�f���̗�ł��B

```php
class Customer extends \yii\elasticsearch\ActiveRecord
{
    /**
     * @return array ���̃��R�[�h�̑����̃��X�g
     */
    public function attributes()
    {
        // '_id' �ɑ΂���p�X�}�b�s���Ois setup to field 'id'
        return ['id', 'name', 'address', 'registration_date'];
    }

    /**
     * @return ActiveQuery Order ���R�[�h �ւ̃����[�V�������`
     * (Order �͑��̃f�[�^�x�[�X�A�Ⴆ�΁Aredis ��ʏ�� SQLDB �ɂ����Ă��ǂ�)
     */
    public function getOrders()
    {
        return $this->hasMany(Order::className(), ['customer_id' => 'id'])->orderBy('id');
    }

    /**
     * `$query` ���C�����ăA�N�e�B�u (status = 1) �Ȍڋq������Ԃ��X�R�[�v���`
     */
    public static function active($query)
    {
        $query->andWhere(['status' => 1]);
    }
}
```

[[yii\elasticsearch\ActiveRecord::index()|index()]] �� [[yii\elasticsearch\ActiveRecord::type()|type()]] ���I�[�o�[���C�h���āA���̃��R�[�h���\���C���f�b�N�X�ƃ^�C�v���`���邱�Ƃ��o���܂��B

elasticsearch �̃A�N�e�B�u���R�[�h�̈�ʓI�Ȏg�p��@�́A[�K�C�h](https://github.com/yiisoft/yii2/blob/master/docs/guide/active-record.md) �Ő������ꂽ�f�[�^�x�[�X�̃A�N�e�B�u���R�[�h�̏ꍇ�Ɣ��ɂ悭���Ă��܂��B
�ȉ��̐����Ɗg�� (*!*) �����邱�Ƃ������΁A�����C���^�[�t�F�C�X�Ƌ@�\���T�|�[�g���Ă��܂��B

- elasticsearch �� SQL ���T�|�[�g���Ă��Ȃ����߁A�N�G���� API �� `join()`�A`groupBy()`�A`having()` ����� `union()` ���T�|�[�g���܂���B
  ���בւ��A���~�b�g�A�I�t�Z�b�g�A�����t�� WHERE �́A���ׂăT�|�[�g����Ă��܂��B
- [[yii\elasticsearch\ActiveQuery::from()|from()]] �̓e�[�u����I�����܂���B
  �����ł͂Ȃ��A�N�G���Ώۂ� [�C���f�b�N�X](http://www.elastic.co/guide/en/elasticsearch/reference/current/glossary.html#glossary-index) �� [�^�C�v](http://www.elastic.co/guide/en/elasticsearch/reference/current/glossary.html#glossary-type) ��I�����܂��B
- `select()` �� [[yii\elasticsearch\ActiveQuery::fields()|fields()]] �ɒu���������Ă��܂��B
  ��{�I�ɂ͓������Ƃ�������̂ł����A`fields` �̕� elasticsearch �̗p��Ƃ��đ��������ł��傤�B
  �h�L�������g����擾����t�B�[���h���`���܂��B
- Elasticsearch �ɂ̓e�[�u��������܂���̂ŁA�e�[�u����ʂ��Ă� [[yii\elasticsearch\ActiveQuery::via()|via]] �����[�V�����͒�`���邱�Ƃ��o���܂���B
- Elasticsearch �̓f�[�^�X�g���[�W�ł���Ɠ����Ɍ����G���W���ł�����܂��̂ŁA���R�Ȃ���A���R�[�h�̌����ɑ΂���T�|�[�g���ǉ�����Ă��܂��B
  Elasticsearch �̃N�G�����\�����邽�߂� [[yii\elasticsearch\ActiveQuery::query()|query()]]�A[[yii\elasticsearch\ActiveQuery::filter()|filter()]] ������ [[yii\elasticsearch\ActiveQuery::addFacet()|addFacet()]] �Ƃ������\�b�h������܂��B
  ����炪�ǂ̂悤�ɓ������ɂ��āA���̎g�p������Ă��������B
  �܂��A`query` �� `filter` �̕������\�������@�ɂ��ẮA[Query DSL](http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl.html) ���Q�Ƃ��Ă��������B
- Elasticsearch �̃A�N�e�B�u���R�[�h����ʏ�̃A�N�e�B�u���R�[�h�N���X�ւ̃����[�V�������`���邱�Ƃ��\�ł��B�܂��A���̋t���\�ł��B

> Note|**����**: �f�t�H���g�ł́Aelasticsearch �́A�ǂ�ȃN�G���ł��A�Ԃ���郌�R�[�h�̐��� 10 �Ɍ��肵�Ă��܂��B
> �����Ƒ����̃��R�[�h���擾���邱�Ƃ���҂���ꍇ�́A�����[�V�����̒�`�ŏ���𖾎��I�Ɏw�肵�Ȃ���΂Ȃ�܂���B
> ���̂��Ƃ́Avia() ���g�������[�V�����ɂƂ��Ă��d�v�ł��B
> �Ȃ��Ȃ�Avia �̃��R�[�h�� 10 �܂łɐ�������Ă���ꍇ�́A�����[�V�����̃��R�[�h�� 10 �𒴂��邱�Ƃ͏o���Ȃ�����ł��B


�g�p��:

```php
$customer = new Customer();
$customer->primaryKey = 1; // ���̏ꍇ�́A$customer->id = 1 �Ɠ���
$customer->attributes = ['name' => 'test'];
$customer->save();

$customer = Customer::get(1); // PK �ɂ���ă��R�[�h���擾
$customers = Customer::mget([1,2,3]); // PK �ɂ���ĕ����̃��R�[�h���擾
$customer = Customer::find()->where(['name' => 'test'])->one(); // �N�G���ɂ��擾�B���R�[�h�𐳂����擾���邽�߂ɂ͂��̃t�B�[���h�Ƀ}�b�s���O���\������K�v�����邱�Ƃɒ��ӁB
$customers = Customer::find()->active()->all(); // �N�G���ɂ���đS�Ă��擾 (`active` �X�R�[�v���g����)

// http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query.html
$result = Article::find()->query(["match" => ["title" => "yii"]])->all(); // articles whose title contains "yii"

// http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-flt-query.html
$query = Article::find()->query([
    "fuzzy_like_this" => [
        "fields" => ["title", "description"],
        "like_text" => "���̃N�G���́A���̃e�L�X�g�Ɏ����L����Ԃ��܂� :-)",
        "max_query_terms" => 12
    ]
]);

$query->all(); // �S�Ẵh�L�������g���擾
// ������ facets ��ǉ��ł���
$query->addStatisticalFacet('click_stats', ['field' => 'visit_count']);
$query->search(); // �S�Ẵ��R�[�h�A����сAvisit_count �t�B�[���h�Ɋւ��铝�v (�Ⴆ�΁A���ρA���v�A�ŏ��A�ő�Ȃ�) ���擾
```

�����āA�܂��A���낢��Ƒ�R����܂��B
"it's endless what you can build"[?](https://www.elastic.co/)
