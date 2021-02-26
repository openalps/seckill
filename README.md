# seckill
基于yii2 redis 秒杀系统

1.data/database.sql   //数据表
use common\models\seckill\Seckill;//秒杀活动表
use common\models\seckill\SeckillGroup;//秒杀活动分组表
use common\models\seckill\SeckillRequest;//记录表

说明
1.系统基于yii2框架 redis缓存
2.SeckillService::doRequest() //用户请求方法
3.SeckillService::consumer() //守护进程维护，消费者会把用户请求数据写到seckill_request,把领取成功的数据传输给回调方法，每个活动配置处理业务的静态方法，如seckill.consumer=\common\service\seckill\call\BonusService::run;
4.SeckillService::runSeckill() //启动活动，启动的时候会把库存limit_count装载到消息队列，同时把用户成功记录统计放到缓存，用于条件限制使用
5.SeckillService::stopSeckill //停止活动，会把活动相关缓存清除掉
6.SeckillService::save() //活动创建更新方法，活动或活动组更新需要重启方生效
7.SeckillService::groupSave() //活动组创建更新方法
8.支持功能：
秒杀系统基于分组，每个分组可创建多个秒杀活动；
可配置限制条件：
1.每个分组每人限领次数，
2.每个分组每人每天限领次数；
3.单个活动每人限领次数，单个活动每人每天限令次数；
4.单个活动发放库存限制；
5.活动状态控制
6.活动时间有效期控制
