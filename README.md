# seckill
秒杀系统
基于yii2 可配置秒杀系统
架构：
秒杀系统基于分组，每个分组可创建多个秒杀活动；
可配置限制条件：每个分组每人限领次数，每个分组每人每天限领次数；单个活动每人限领次数，单个活动每人每天限令次数；单个活动发放库存限制；
使用:SeckillService::doRequest( $seckillcode, $userid, $platfromid  )
守护进程维护消费者：SeckillService::consumer()

部署：
1.data/database.sql   //数据表
