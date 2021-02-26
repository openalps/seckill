<?php
/**
 * 秒杀中心
 * author:openskyli
 * @912705075@qq.com
 * 2021-02-23
 */
namespace common\services\seckill;
use Yii;
use common\models\seckill\Seckill;
use common\models\seckill\SeckillGroup;
use common\models\seckill\SeckillRequest;
use common\services\LogService;
class SeckillService 
{


	  public static $seckills = array();

	  public static $request_data = array();

       /**
       * 缓存键名mkey
       * @param  [type] $seckillcode [description]
       * @return [type]              [description]
       */
       public static function buildSeckillMkey( $seckillcode )
       {
   	   	   return join(":",[
   	   	  	   'find',
   	   	  	   $seckillcode
   	   	   ]);
       }
      
       /**
        * 消费者队列mkey
        * @param  [type] $seckillcode [description]
        * @return [type]              [description]
        */
       public static function buildConsumerQueueMkey( $seckillcode )
       {
       	    return join(':',[
       	    	'queue',
       	    	'consumer',
       	    	$seckillcode
       	    ]);
       }
       /**
        * 库存消息队列mkey
        * @param  [type] $seckillcode [description]
        * @return [type]              [description]
        */
       public static function buildStockQueueMkey( $seckillcode )
       {
	       	   return join(':',[
		       	   	'queue',
		       	   	'stock',
		       	   	$seckillcode
	       	   ]);
       }
       /**
        * 所有运营中的活动
        * @return [type] [description]
        */
       public static function buildAllSeckillMkey()
       {
	       	   return join(":",[
	       	   	  'heihu',
	       	   	  'all',
	       	   	  'seckill',
	       	   	  'ruinning'
	       	   ]);
       }

       public static function buildReceiveMkey($seckillcode,$userid)
       {
	       	    return join(':',[
	       	    	  'receive',
	       	    	  $seckillcode,
	       	    	  $userid
	       	    ]);
       }
       public static function buildReceiveGroupMkey($groupcode,$userid)
       {
	       	   return join(':',[
	       	   	  'receive',
	       	   	  'group',
	       	   	  $groupcode,
	       	   	  $userid
	       	   ]);
       }
       public static function buildReceiveDayMkey($seckillcode,$userid,$date)
       {
	       	   return join(':',[
	       	   	  'receive',
	       	   	  'day',
	       	   	  $seckillcode,
	       	   	  $userid,
	       	   	  $date
	       	   ]);
       }
       public static function buildReceiveDayGroupMkey($groupcode,$userid,$date)
       {
	       	  return join(':',[
	       	  	  'receive',
	       	  	  'day',
	       	  	  'group',
	       	  	  $groupcode,
	       	  	  $userid,
	       	  	  $date
	       	  ]);
       }

      

	   /**
	    * 请求处理方法
	    * @param  [type] $userid [description]
	    * @return [type]         [description]
	    */
	   public static function doRequest( string $seckillcode, int $userid, int $platfromid=1):array
	   {
	   	        //消费者队列key
		        $consumer_queue_mkey = self::buildConsumerQueueMkey( $seckillcode );
	   			$redisIns = self::getRedisIns();

	   			if( !($seckill = self::$seckills[ $seckillcode ]) )
	   			{
		            $seckill = self::getSeckillByCode( $seckillcode );
	   			}

	   		    try {

	    	            if( empty($seckill) )
	    	            {
	    	            	throw new \Exception("该活动不存在!", 1001);
	    	            	
	    	            }
	    	            //开始有效时间判断
	    	            if( (int)$seckill['start_time']>time() )
	    	            {
	    	            	throw new \Exception("该活动未开始，请耐心等待!", 1002);
	    	            }
	    	            //有效期判断
	    	            if( (int)$seckill['end_time'] < time() )
	    	            {
	    	            	throw new \Exception("该活动已结束!", 1003);
	    	            }
	    	            // 状态判断
	    	            if( (int)$seckill['status_val']!==Seckill::STATUS_VAL_RUNING )
	    	            {
	    	            	throw new \Exception("该活动已停止!", 1004);
	    	            }

	    	           

	    	            // 每人限领取次数
	    	            if(  (int)$seckill['per_user_limit']>0 
	    	            	&& self::getUserReceiveTimesFromCache( $seckillcode, $userid )>=(int)$seckill['per_user_limit']  )
	    	            {
	    	            	throw new \Exception(join('',[
	    	            		"活动每人限领次数:",
	    	            		$seckill['per_user_limit'],
	    	            		'次,您已经领过啦!'
	    	            	]), 1005);
	    	            	
	    	            }

	    	            //每人分组下限领取次数
	    	            if( (int)$seckill['per_user_group_limit']>0 
	    	            	&& self::getUserReceiveGroupTimesFromCache( $seckill['group_code'], $userid )>=$seckill['per_user_group_limit'] )
	    	            {
	    	            	throw new \Exception(join('',[
	    	            		"活动组每人限领次数:",
	    	            		$seckill['per_user_group_limit'],
	    	            		'次,您已经领过啦!'
	    	            	]), 1006);
	    	            	
	    	            }
	    	        
	    	            //每人每天限领次数
	    	            if ( (int)$seckill['per_user_day_limit']>0 
	    	            	&& self::getUserReceiveTimesDayFromCache( $seckillcode,$userid )>=(int)$seckill['per_user_day_limit'] ) 
	    	            {
	    	            	throw new \Exception(join('',[
	    	            		"活动每人每天限领次数:",
	    	            		$seckill['per_user_day_limit'],
	    	            		"次,您已经领过啦!"
	    	            	]), 1007);
	    	            }

	    	            //每人每天分组下限领
	    	            if ( (int)$seckill['per_user_day_group_limit']>0 
	    	            	&& self::getUserReceiveGroupTimesDayFromCache( $seckill['group_code'],$userid )>=(int)$seckill['per_user_day_group_limit'] ) 
	    	            {
	    	            	throw new \Exception(join('',[
	    	            		"活动组每人每天限领次数:",
	    	            		$seckill['per_user_day_group_limit'],
	    	            		"次,您已经领过啦!"
	    	            	]), 1008);
	    	            }
	    	          
	    	            //库存队列key
	    	            $stock_queue_mkey = self::buildStockQueueMkey( $seckillcode );
	    	          
	    	            // 拉出库存
	    	            $num = $redisIns->rpop( $stock_queue_mkey );
	    	            if( !empty($num) )
	    	            {
	    	            	// 每人限领取次数
	    	            	if(  (int)$seckill['per_user_limit']>0 )
	    	            	{
	    	            		$per_user_mkey = self::buildReceiveMkey( $seckillcode, $userid );
	    	            		self::incr($per_user_mkey);
	    	            	}

	    	            	//每人分组下限领取次数
	    	            	if( (int)$seckill['per_user_group_limit']>0 )
	    	            	{
	    	            		$per_user_group_mkey = self::buildReceiveGroupMkey( $seckill['group_code'], $userid );
	    	            		self::incr($per_user_group_mkey);
	    	            	}
	    	            	//每人每天限领次数
	    	            	if ( (int)$seckill['per_user_day_limit']>0 ) 
	    	            	{
	    	            		$per_user_day_mkey = self::buildReceiveDayMkey( $seckillcode,$userid, date('Ymd') );
	    	            		self::incr($per_user_day_mkey);
	    	            	}
	    	            	//每人每天分组下限领
	    	            	if ( (int)$seckill['per_user_day_group_limit']>0 ) 
	    	            	{
	    	            		$per_user_day_group_mkey = self::buildReceiveDayGroupMkey($seckill['group_code'],$userid, date('Ymd'));
	    	            		self::incr($per_user_day_group_mkey);
	    	            	}
	    	            	
	    	            	// 保存到消费者队列
	    	            	$redisIns->lpush( $consumer_queue_mkey, json_encode([
	    	            		'create_time'=>self::microsecond(),
	    	            		'num'=>$num,
	    	            		'group_code'=>$seckill['group_code'],
	    	            		'platfromid'=>$platfromid,
	    	            		'code'=>0,
	    	            		'user_id'=>$userid
	    	            	]));
	    	            	return [0, $seckill['tip_success_text']??'恭喜，领取成功'];
	    	            }

	    	            throw new \Exception($seckill['tip_unstock_text']??"手速慢，已经被领完啦!", 1009);

	   		    } catch (\Exception $e) 
	   		    {
	   		    	 if(  !empty($e->getCode()) && (int)$e->getCode()>1000 && (int)$e->getCode()<10010   )
	   		    	 {
		   		    	 $redisIns->lpush( $consumer_queue_mkey, json_encode([
		   		    	 	'create_time'=>self::microsecond(),
		   		    	 	'num'=>-1,
		   		    	 	'group_code'=>$seckill['group_code'],
		   		    	 	'platfromid'=>$platfromid,
		   		    	 	'code'=>$e->getCode(),
		   		    	 	'user_id'=>$userid
		   		    	 ]));
	   		    	 }else
	   		    	 {
	   		    	 	 LogService::log( '/seckill/request_error','ERROR', $e->getMessage(), [
	   		    	 	 	 'platfromid'=>$platfromid,
	   		    	 	 	 'userid'=>$userid,
	   		    	 	 	 'seckillcode'=>$seckillcode
	   		    	 	 ]);
	   		    	 }
	   		    	 return [$e->getCode(),$e->getMessage()];
	   		    }
	   			
	            

	            
	   }
	   /**
	    * 获取用户领取次数
	    * @param  [type] $userid 
	    * @return [type]          
	    */
	   public static function getUserReceiveTimesFromCache($seckillcode, $userid )
	   {
	   	     $mkey = self::buildReceiveMkey( $seckillcode, $userid );
	   	     return self::getRedisCache( $mkey )??0;
	   }
	   /**
	    * 获取用户在分组下领取次数
	    * @param  [type] $groupcode   
	    * @param  [type] $userid      
	    * @return [type]              
	    */
	   public static function getUserReceiveGroupTimesFromCache($groupcode,  $userid )
	   {
	   	    $mkey = self::buildReceiveGroupMkey( $groupcode, $userid );
	   	    return self::getRedisCache( $mkey )??0;
	   }
	   /**
	    * 获取用户当天领取次数
	    * @param  [type] $userid 
	    * @return [type]          
	    */
	   public static function getUserReceiveTimesDayFromCache( $seckillcode, $userid  )
	   {
	   	     $mkey = self::buildReceiveDayMkey( $seckillcode, $userid, date('Ymd') );
	   	     return self::getRedisCache( $mkey )??0;
	   }
	   /**
	    * 获取用户当天分组下领取次数
	    * @param  [type] $groupcode
	    * @param  [type] $userid   
	    * @return [type]           
	    */
	   public static function getUserReceiveGroupTimesDayFromCache( $groupcode, $userid  )
	   {
	     	$mkey = self::buildReceiveDayGroupMkey( $groupcode, $userid, date('Ymd') );
	   	    return self::getRedisCache( $mkey )??0;
	   }
       /**
        * 
        * @param  [type] $seckillcode [description]
        * @return [type]              [description]
        */
	   public static function getSeckillByCode( $seckillcode )
	   {
	   	      $mkey = self::buildSeckillMkey( $seckillcode );
		   	  $data = self::getRedisCache( $mkey );
		   	  if( empty($data) ) 
		   	  {
		   	  	 $seckillIns = Seckill::findOne([
		   	  	 	'seckill_code'=>$seckillcode
		   	  	 ]);
		   	  	 if( !empty($seckillIns) )
		   	  	 {
		   	  	 	$seckillGroupIns = SeckillGroup::findOne([
		   	  	 		'group_code'=>$seckillIns->group_code
		   	  	 	]);
		   	  	 	$data = $seckillIns->toArray();
		   	  	 	if( !empty($seckillGroupIns) )
		   	  	 	{
			   	  	 	$data['per_user_day_group_limit'] = $seckillGroupIns->per_user_day_group_limit;
			   	  	 	$data['per_user_group_limit'] = $seckillGroupIns->per_user_group_limit;
			   	  	 }else
			   	  	 {
			   	  	 	$data['per_user_day_group_limit'] = 0;
			   	  	 	$data['per_user_group_limit'] = 0;
			   	  	 }
		   	  	 	$data = json_encode($data);
		   	  	 	self::setRedisCahce($mkey,$data,86400);
		   	  	 }
		   	  }
		   	  return json_decode($data,true);
	   }
	   /**
	    * 自增
	    * @param  [type] $mkey [description]
	    * @return [type]       [description]
	    */
	   public static function incr( $mkey )
	   {
	   	    $value = self::getRedisCache($mkey);
	   	    if( empty($value) )
	   	    {
	   	       return self::setRedisCahce($mkey,1);
	   	    }
	   		return self::getRedisIns()->incr($mkey);
	   }
	   /**
	    * 获取缓存
	    * @param  [type] $mkey [description]
	    * @return [type]       [description]
	    */
	   public static function getRedisCache( $mkey )
	   {
	   	   return self::getRedisIns()->get( $mkey );
	   }
	   /**
	    * 其他架构修改此方法即可
	    * @return [type] [description]
	    */
	   public static function getRedisIns()
	   {
	   	   return \Yii::$app->redis;
	   }
	   /**
	    * 设置缓存
	    * @param [type] $mkey   [description]
	    * @param [type] $value  [description]
	    * @param [type] $expire [description]
	    */
	   public static function setRedisCahce( $mkey, $value, $expire=null )
	   {
	   	   self::getRedisIns()->set($mkey,$value);
	   	   if( !empty($expire) )
	   	   {
	   	   	  self::getRedisIns()->expire( $mkey, $expire );
	   	   }
	   	   return true;
	   }
	   
	   /**
	    * 清除缓存中的数据并且缓存
	    * @param  [type] $seckillcode 
	    * @return [type]              
	    */
	   public static function clearSeckill( $seckillcode )
	   {
	   	   self::getRedisIns()->set( self::buildSeckillMkey( $seckillcode ),null );
	   	   self::getSeckillByCode( $seckillcode );
	   	   return true;
	   }


	   /**
	    * 消费者
	    * 需要把这个放进守护进程
	    */
	   public static function consumer()
	   {
		   while (true) {
			   	   $seckills = self::getAllSeckill();
				   	foreach ($seckills as $seckillcode => $value) 
				   	{

				   		   $consumer = $value['consumer'];
				   		   $related_activity_code = $value['related_activity_code'];

				   	   	   self::$request_data = array(
				   	   	   	  $seckillcode=>array()
				   	   	   );

			   	   	   	    $mkey = self::buildConsumerQueueMkey( $seckillcode );
			   	   	   	    $className = explode('::',$consumer)[0];
			   	   	   	    $funName = explode('::',$consumer)[1];

			   	   	   	    try {
			   	   	   	    	
				   	   	   	    while ( $data = self::getRedisIns()->rpop( $mkey ) ) 
				   	   	   	    {
				   	   	   	    	    LogService::log("/seckill/consumer",'INFO','INFO',[
				   	   	   	    	    	'data'=>$data,
				   	   	   	    	    	'seckillvalue'=>$value,
				   	   	   	    	    	'seckillcode'=>$seckillcode
				   	   	   	    	    ]);
						   	   	   	    $data = json_decode($data,true);
						   	   	   	    self::$request_data[ $seckillcode ][] = array(
						   	   	   	    	'seckill_code'=>$seckillcode,
						   	   	   	    	'create_time'=>$data['create_time'],
						   	   	   	    	'status'=>(int)$data['num']>0?SeckillRequest::STATUS_SUCCESS:SeckillRequest::STATUS_FAIL,
						   	   	   	    	'num'=>$data['num'],
						   	   	   	    	'group_code'=>$data['group_code'],
						   	   	   	    	'platfromid'=>$data['platfromid'],
						   	   	   	    	'user_id'=>$data['user_id'],
						   	   	   	    	'code'=>$data['code']
						   	   	   	    );

						   	   	   	    if( count( self::$request_data[$seckillcode] )>=200 )
						   	   	   	    {
						   	   	   	    	self::insertRequest( self::$request_data[$seckillcode] );
   	    	   	    	   	   	   	    	if( !empty( self::getRequestSuccessRows(self::$request_data[$seckillcode]) ) )
   	    	   	    	   	   	   	    	{
   	    		   	    	   	   	   	    	$className::$funName( array(
   	    		   	    	   	   	   	    		'rows'=>self::getRequestSuccessRows(self::$request_data[$seckillcode]),
   	    		   	    	   	   	   	    		'related_activity_code'=>$related_activity_code,
   	    		   	    	   	   	   	    		'seckillcode'=>$seckillcode
   	    		   	    	   	   	   	    	));
   	    	   	    	   	   	   	    	}
						   	   	   	    	self::$request_data[$seckillcode] = array();
						   	   	   	    }
						   	   	   	   
				   	   	   	    }
	   	   	       	   	   	   
	   	       	   	   	    	if( !empty(self::$request_data[$seckillcode]) )
	   	       	   	   	    	{
	   	    	   	   	   	    	self::insertRequest( self::$request_data[$seckillcode] );
	   	    	   	   	   	    	if( !empty( self::getRequestSuccessRows(self::$request_data[$seckillcode]) ) )
	   	    	   	   	   	    	{
		   	    	   	   	   	    	$className::$funName( array(
		   	    	   	   	   	    		'rows'=>self::getRequestSuccessRows(self::$request_data[$seckillcode]),
		   	    	   	   	   	    		'related_activity_code'=>$related_activity_code,
		   	    	   	   	   	    		'seckillcode'=>$seckillcode
		   	    	   	   	   	    	));
	   	    	   	   	   	    	}
	   	       	   	   	    	}
	   	       	   	   	    	self::$request_data[$seckillcode] = array();

			   	   	   	    } catch (\Exception $e) {
			   	   	   	    	LogService::log("/seckill/consumer_error",'ERROR','消费者错误',$e->getMessage());
			   	   	   	    }
			   	   	   	    

		   	   	   }
		   	   	   var_dump('sleep 2s...');
		   	   	   sleep(2);
		   	   	   continue ;
	   	   }
	   }
	   /**
	    * 过滤成功的记录
	    * @return [type] [description]
	    */
	   public static function getRequestSuccessRows( $rows )
	   {
	   	   $data = array();
	   	   foreach ($rows as $key => $value) {
	   	   	  if( $value['status']==SeckillRequest::STATUS_SUCCESS )
	   	   	  {
	   	   	  	  $data[] = $value;
	   	   	  }
	   	   }
	   	   return $data;
	   }
	   /**
	    * 请求
	    * @param  [type] $lData [description]
	    * @return [type]        [description]
	    */
	   public static function insertRequest( $lData )
	   {
	   	      $res = \Yii::$app->db->createCommand()->batchInsert('seckill_request', 
               [
                   'seckill_code',
                   'create_time',
                   'status',
                   'num',
                   'group_code',
                   'platfromid',
                   'user_id',
                   'code'
               ], $lData)->execute();
	   	      if( !empty($lData[0]) )
	   	      {
		   	      self::updateSeckillSuccessCount( $lData[0]['seckill_code'] );
	   	      }
	   }
	   /**
	    * 获取消费者队列的长度
	    * @param  [type] $seckillcode 
	    * @return [type]              
	    */
	   public static  function getConsumerQueueLen( $seckillcode )
	   {
	   	   return (int)self::getRedisIns()->llen( $seckillcode );
	   }
	   /**
	    * 毫秒级别
	    * @return [type] [description]
	    */
	   public static function  microsecond()
       {
	           $t = explode(" ", microtime());
	           $microsecond = round(round($t[1].substr($t[0],2,3)));
	           return $microsecond;
       }
	   
	   
	   /**
	    * 获取所有运行中的秒杀活动
	    */
	   public static function getAllSeckill()
	   {
	   	    $seckills = self::getRedisCache( self::buildAllSeckillMkey() );

	   	    return empty($seckills)?[]:json_decode($seckills,true);
	   }
	   /**
	    * 更新活动领取成功失败数量
	    * @param  [type] $seckillcode [description]
	    * @return [type]              [description]
	    */
	   public static function updateSeckillSuccessCount( $seckillcode )
	   {
	   	   $sql="update 
				seckill
				INNER JOIN 
				(
				SELECT
				'{$seckillcode}' seckill_code,
				(SELECT count(*) from seckill_request where status=".SeckillRequest::STATUS_SUCCESS." AND seckill_code='{$seckillcode}') success_count,
				(SELECT count(*) from seckill_request where status=".SeckillRequest::STATUS_FAIL." AND seckill_code='{$seckillcode}') fail_count
				) t 
				ON t.seckill_code=seckill.seckill_code
				set 
				seckill.success_count = t.success_count,
				seckill.fail_count = t.fail_count";
			$res = \Yii::$app->db->createCommand($sql)->execute();
			return $res;
	   }


		   /**
		    * 
		    * @param  array  $aParams 
		    * @return [type]          
		    */
		   public static function save( $aParams=array() )
		   {

			   	   
			   	   if( !empty($aParams['id']) )
		   	   	   {
		   		   	   $Seckill = Seckill::findOne( $aParams['id'] );
		   	   	   }else
		   	   	   {
		   	   	   	   $Seckill = new Seckill();
		   	   	   	   $Seckill->create_time = time();
		   	   	   	   $Seckill->seckill_code = 'S'.date('YmdHis');
		   	   	   	   $Seckill->status_val = Seckill::STATUS_VAL_STOPED;
		   	   	   }
			   	   $Seckill->name = $aParams['name'];
			   	   $Seckill->note = $aParams['note'];
			   	   $Seckill->group_code = $aParams['group_code'];
			   	   $Seckill->start_time = $aParams['start_time'];	
			   	   $Seckill->end_time = $aParams['end_time'];	
			   	   $Seckill->limit_count = $aParams['limit_count'];
			   	   $Seckill->tip_success_text = $aParams['tip_success_text'];
			   	   $Seckill->tip_unstock_text = $aParams['tip_unstock_text'];
			   	   $Seckill->per_user_limit = $aParams['per_user_limit'];	
			   	   $Seckill->per_user_day_limit = $aParams['per_user_day_limit'];	
			   	   $Seckill->related_activity_code = $aParams['related_activity_code'];	
			   	   $Seckill->consumer = $aParams['consumer'];	
			   	   $Seckill->admin_id = $aParams['admin_id'];	
			   	   $Seckill->update_time = time();
			   	   $res = $Seckill->save();
			   	   if( empty($res) )
			   	   {
			   	   	  return [100002,'保存失败',''];
			   	   }

			   	   self::clearSeckill( $Seckill->seckill_code );

			   	   return [0,'保存成功'];
		   }
		   /**
		    * 复制秒杀活动
		    * @param  [type] $seckillcode [description]
		    * @return [type]              [description]
		    */
		   public static function copySeckill( $seckillId )
		   {
			   	  $seckillIns = Seckill::findOne($seckillId);
			   	  if( empty($seckillIns) )
			   	  {
			   	  	 return [100002,'秒杀不存在'];
			   	  }
			   	  $seckill_data = $seckillIns->toArray();
			   	  $seckill_data['name'] = $seckill_data['name'].'_复制';
			   	  unset($seckill_data['id']);
			   	  list($code,$message) = self::save( $seckill_data );
			   	  $message = $code==0?'复制秒杀活动成功':'复制秒杀活动失败';
			   	  return [$code,$message];
		   }
	      /**
	       * 
	       * @param  array  $aParams 
	       * @return [type]          
	       */
	      public static function groupSave( $aParams=array() )
	      {

	   	   	   if( !empty($aParams['id']) )
	   	   	   {
	   		   	   $SeckillGroup = SeckillGroup::findOne( $aParams['id'] );
	   	   	   }else
	   	   	   {
	   	   	   	   $SeckillGroup = new SeckillGroup();
	   	   	   	   $SeckillGroup->create_time = time();
	   	   	   	   $SeckillGroup->group_code = 'G'.date('YmdHis');
	   	   	   }
	   	   	   $SeckillGroup->name = $aParams['name'];
	   	   	   $SeckillGroup->note = $aParams['note'];
	   	   	   $SeckillGroup->per_user_day_group_limit = $aParams['per_user_day_group_limit'];	
	   	   	   $SeckillGroup->per_user_group_limit = $aParams['per_user_group_limit'];	
	   	   	   $SeckillGroup->admin_id = $aParams['admin_id'];	
	   	   	   $SeckillGroup->update_time = time();
	   	   	   $res = $SeckillGroup->save();
	   	   	   if( empty($res) )
	   	   	   {
	   	   	   	  return [100002,'保存失败'];
	   	   	   }
	   	   	   if( !empty($aParams['id']) )
	   	   	   {
		   	   	   $group_seckill = Seckill::find()
		   	   	   ->where([
		   	   	   	  'group_code'=>$SeckillGroup->group_code
		   	   	   ])->asArray()->all();
		   	   	   foreach ($group_seckill as $key => $value) {
		   	   	   	  self::clearSeckill( $value['seckill_code'] );
		   	   	   }
	   	   	   }
	   	   	   return [0,'保存成功'];
	      }
         /**
          * 删除
          * @param   $seckillcode 
          * @return               
          */
         public static function groupDelete( $id )
         {

      	   	   $SeckillGroup = SeckillGroup::findOne($id);

      	   	   $seckill_count = Seckill::find()
      	   	   ->where([
      	   	   	  'group_code'=>$SeckillGroup->group_code,
      	   	   	  'del_flag'=>0
      	   	   ])->count();
      	   	   if( !empty($seckill_count) )
      	   	   {
      	   	   	   return [10003,'该分组下还有活动，不能删除!!'];
      	   	   }
      	   	   $res = $SeckillGroup->delete();
      	   	   if( empty($res) )
      	   	   {
      	   	   	  return [100002,'删除失败'];
      	   	   }

      	   	   return [0,'删除成功'];
         }
	   /**
	    * [getCacheTime description]
	    * @param  [type] $endTime [description]
	    * @return [type]          [description]
	    */
	   public static function getCacheTime( $endTime )
	   {
	   	   return ($endTime - time())>0?$endTime-time():10*86400;
	   }
	   /**
	    * 删除
	    * @param   $seckillcode 
	    * @return               
	    */
	   public static function delete( $id )
	   {	
	   	       $Seckill = Seckill::findOne($id);

	   	       if( $Seckill->status_val==Seckill::STATUS_VAL_RUNING )
	   	       {
	   	       	   return [100003,'活动停止之后才能删除'];
	   	       }
	   	       $Seckill->del_flag = Seckill::DEL_FLAG_Y;
	   	       $Seckill->update_time = time();
		   	   $res = $Seckill->save();

		   	   if( empty($res) )
		   	   {
		   	   	  return [100002,'删除失败'];
		   	   }
		   	   return [0,'删除成功'];
	   }
	 
	   
	   /**
	    * 获取领取成功数量
	    * @param  [type] $seckillcode 
	    * @return [type]              
	    */
	   public static function getSeckillSuccessCount( $seckillcode )
	   {
		   	   return (int)SeckillRequest::find()
		   	   ->where([
		   	   	  'seckill_code'=>$seckillcode,
		   	   	  'status'=>SeckillRequest::STATUS_SUCCESS
		   	   ])->count();
	   }
	   /**
	    * 删除库存队列
	    * @return [type] [description]
	    */
	   public static function deleteStockQueue( $seckillcode )
	   {
	   	   $stock_queue_mkey = self::buildStockQueueMkey( $seckillcode );
	   	   while (self::getRedisIns()->rpop( $stock_queue_mkey )) {
	   	   }
	   	   return true;
	   }
	   /**
	    * 删除消费者队列数据
	    * @param  [type] $seckillcode [description]
	    * @return [type]              [description]
	    */
	   public static function deleteConsumerQueue( $seckillcode )
	   {
	   	   $consumer_queue_mkey = self::buildConsumerQueueMkey( $seckillcode );
	   	   while ($json=self::getRedisIns()->rpop( $consumer_queue_mkey )) {
	   	   }
	   	   return true;
	   }

	   /**
	    * 获取用户领取次数
	    * @param  [type] $userid 
	    * @return [type]          
	    */
	   public static function setUserReceiveTimesToCahce($seckillcode,$isDelete=false)
	   {
	   	     $sql=" SELECT 
			   	     user_id,
			   	     seckill_code,
			   	     count( * ) count 
			   	     FROM seckill_request 
			   	     WHERE 
			   	     `status`= ".SeckillRequest::STATUS_SUCCESS." 
			   	     AND seckill_code='{$seckillcode}' 
			   	     GROUP BY 
			   	     user_id,
			   	     seckill_code";
	   	     $data = \Yii::$app->db->createCommand($sql)->queryAll();
	   	     if( empty($data) )
	   	     {
	   	     	return ;
	   	     }
	   	     $SeckillIns = Seckill::findOne([
	   	     	'seckill_code'=>$seckillcode
	   	     ]);
	   	     foreach ($data as $key => $value) {
	   	     	$mkey = self::buildReceiveMkey( $value['seckill_code'], $value['user_id'] );
	   	     	if( $isDelete==true  )
	   	     	{
	   	     		self::getRedisIns()->set( $mkey, null );
	   	     		continue ;
	   	     	}
	   	     	self::setRedisCahce( $mkey, (int)$value['count'], self::getCacheTime( $SeckillIns->end_time ) );
	   	     }
	   	     return 0;
	   }
	   /**
	    * 获取用户在分组下领取次数
	    * @param  [type] $groupcode   
	    * @param  [type] $userid      
	    * @return [type]              
	    */
	   public static function setUserReceiveGroupTimesToCache($groupcode,$isDelete=false)
	   {
	   	    $sql="  SELECT 
			   	    user_id,
			   	    group_code,
			   	    count(*) count 
			   	    from seckill_request 
			   	    where 
			   	    status=".SeckillRequest::STATUS_SUCCESS."  
			   	    AND group_code='{$groupcode}' 
			   	    GROUP BY 
			   	    user_id,
			   	    group_code";
	   	    $data = \Yii::$app->db->createCommand($sql)->queryAll();
	   	    if( empty($data) )
	   	    {
	   	    	return ;
	   	    }
	   	    $SeckillIns = Seckill::findOne([
	   	    	'seckill_code'=>$seckillcode
	   	    ]);
	   	    foreach ($data as $key => $value) {
	   	    	$mkey = self::buildReceiveGroupMkey( $value['group_code'], $value['user_id'] );
	   	    	if( $isDelete==true  )
	   	    	{
	   	    		self::getRedisIns()->set( $mkey, null );
	   	    		continue ;
	   	    	}
	   	    	self::setRedisCahce( $mkey, (int)$value['count']);
	   	    }
	   	    return 0;
	   }
	   /**
	    * 获取用户当天领取次数
	    * @param  [type] $userid 
	    * @return [type]          
	    */
	   public static function setUserReceiveTimesDayToCache( $seckillcode,$isDelete=false )
	   {
	   	     $sql=" SELECT
					user_id,
					seckill_code,
					FROM_UNIXTIME(left(create_time,10),'%Y%m%d') `date`,
					count(*) count
					from 
					seckill_request 
					where 
					status=".SeckillRequest::STATUS_SUCCESS."
					AND FROM_UNIXTIME(left(create_time,10),'%Y%m%d')='".date('Ymd')."'
					GROUP BY
					user_id,
					seckill_code";
			 $data = \Yii::$app->db->createCommand($sql)->queryAll();
			 if( empty($data) )
			 {
			 	return ;
			 }
			 
			 foreach ($data as $key => $value) {
			 	$mkey = self::buildReceiveDayMkey( $value['seckill_code'], $value['user_id'], $value['date'] );
			 	if( $isDelete==true  )
	   	     	{
	   	     		self::getRedisIns()->set( $mkey, null );
	   	     		continue ;
	   	     	}
			 	// 有效期设置为一天，一天过后该数据已失效
			 	self::setRedisCahce( $mkey, (int)$value['count'], 86400 );
			 }
	   	     return 0;
	   }
	   /**
	    * 获取用户当天分组下领取次数
	    * @param  [type] $groupcode
	    * @param  [type] $userid   
	    * @return [type]           
	    */
	   public static function setUserReceiveGroupTimesDayToCache( $groupcode,$isDelete=false)
	   {
       	     $sql=" SELECT
    				user_id,
    				group_code,
    				FROM_UNIXTIME(left(create_time,10),'%Y%m%d') `date`,
    				count(*) count
    				from 
    				seckill_request 
    				where 
    				status=".SeckillRequest::STATUS_SUCCESS."
    				AND FROM_UNIXTIME(left(create_time,10),'%Y%m%d')='".date('Ymd')."'
    				GROUP BY
    				user_id,
    				group_code";
    		$data = \Yii::$app->db->createCommand($sql)->queryAll();
    		if( empty($data) )
    		{
    			return ;
    		}

    		foreach ($data as $key => $value) {
    			$mkey = self::buildReceiveDayGroupMkey( $value['group_code'], $value['user_id'], $value['date'] );
    			if( $isDelete==true  )
    			{
    				self::getRedisIns()->set( $mkey, null );
    				continue ;
    			}
    			// 有效期设置为一天，一天过后该数据已失效
    			self::setRedisCahce( $mkey, (int)$value['count'], 86400 );
    		}

	   	    return 0;
	   }

      /**
       * 启动
       * @param  [type] $seckillcode [description]
       * @return [type]              [description]
       */
      public static function runSeckill( $seckillcode )
      {	
   	   	   $SeckillIns = Seckill::findOne([
   	   	    	'seckill_code'=>$seckillcode
   	   	   ]);
   	   	   if ( empty($SeckillIns) ) 
   	   	   {
   	   	     	return [10003,'活动不存在'];
   	   	   }
   	   	   // 检查消息队列的数据
   	   	   if( self::getConsumerQueueLen( $seckillcode )>0 )
   	   	   {
   	   	   	  return [10002,'消费者队列还有未消费数据，不能重新启动，稍后再试~~'];
   	   	   }
   	   	   // 更新数据
   	   	   $SeckillIns->update_time = time();
   	   	   $SeckillIns->run_time = time();
   	   	   $SeckillIns->status_val = Seckill::STATUS_VAL_RUNING;
   	   	   $res = $SeckillIns->save();
   	   	   if( empty($res) )
   	   	   {
   	   	   	  return [100004,'活动运行失败'];
   	   	   }
   	   	   // 清除活动缓存
   	   	   self::clearSeckill( $seckillcode );
   	   	   //删除库存队列数据
   	   	   self::deleteStockQueue( $seckillcode );
   	   	   //删除消费者队列数据
   	   	   self::deleteConsumerQueue( $seckillcode );
   	   	   //判断已经领取的与库存比较

   	   	   if( self::getSeckillSuccessCount( $seckillcode )>=$SeckillIns->limit_count )
   	   	   {
   	   	   	  return [100005,'该活动已领取完，需要重新设置库存或者新增活动'];
   	   	   }
   	   	   // 已领取数量+1
   	   	   $start_num = self::getSeckillSuccessCount( $seckillcode ) + 1;
   	   	   // 装载数据到库存队列
   	   	   $stock_queue_mkey = self::buildStockQueueMkey( $seckillcode );
   	   	   for($i=$start_num; $i <= $SeckillIns->limit_count;$i++) 
   	   	   { 
   	   	   	   self::getRedisIns()->lpush( $stock_queue_mkey, $i );
   	   	   }

   	   	   // 状态运行中的活动到缓存，以便消费者使用
   	   	   $seckills = self::getAllSeckill();
   	   	   $seckills[ $seckillcode ] = array(
   	   	   	  'consumer'=>$SeckillIns->consumer,
   	   	   	  'related_activity_code'=>$SeckillIns->related_activity_code
   	   	   );
   	   	   self::setRedisCahce( self::buildAllSeckillMkey(), json_encode($seckills) );

   	   	   // 获取用户领取次数
   	   	   self::setUserReceiveTimesToCahce( $seckillcode );
   	   	   //获取用户在分组下领取次数
   	   	   self::setUserReceiveGroupTimesToCache( $SeckillIns->group_code );
   	   	   //获取用户当天领取次数
   	   	   self::setUserReceiveTimesDayToCache( $seckillcode );
   	   	   //获取用户当天分组下领取次数
   	   	   self::setUserReceiveGroupTimesDayToCache( $SeckillIns->group_code );
   	   	   // 更新数据
   	   	   self::updateSeckillSuccessCount( $seckillcode );


   	   	   return [0,'启动活动成功'];
      }
	   /**
	    * 停止
	    * @param  [type] $seckillcode [description]
	    * @return [type]              [description]
	    */
	   public static function stopSeckill( $seckillcode )
	   {
		   	  if( self::getConsumerQueueLen( $seckillcode )>0 )
		   	  {
		   	  	  return [10002,'消费者队列还有未消费数据，不能停止，稍后再试~~'];
		   	  }
		   	  $SeckillIns = Seckill::findOne([
		   	   	'seckill_code'=>$seckillcode
		   	  ]);
		   	  if ( empty($SeckillIns) ) 
		   	  {
		   	    	return [10003,'活动不存在'];
		   	  }
		   	  // 更新数据
		   	  $SeckillIns->update_time = time();
		   	  $SeckillIns->stop_time = time();
		   	  $SeckillIns->status_val = Seckill::STATUS_VAL_STOPED;
		   	  $res = $SeckillIns->save();
		   	  if( empty($res) )
		   	  {
		   	  	  return [100004,'停止活动失败'];
		   	  }
		   	  // 清除活动缓存
		   	  self::clearSeckill( $seckillcode );
		   	  //删除库存队列数据
		   	  self::deleteStockQueue( $seckillcode );
		   	  //删除消费者队列数据
		   	  self::deleteConsumerQueue( $seckillcode );

		   	  // 从运行中的活动中删除
		   	  $seckills = self::getAllSeckill();
		   	  unset($seckills[ $seckillcode ]);
		   	  self::setRedisCahce( self::buildAllSeckillMkey(), json_encode($seckills) );

		   	  // 删除：获取用户领取次数
		   	  self::setUserReceiveTimesToCahce( $seckillcode, true );
		   	  //删除：获取用户在分组下领取次数
		   	  self::setUserReceiveGroupTimesToCache( $SeckillIns->group_code, true );
		   	  //删除：获取用户当天领取次数
		   	  self::setUserReceiveTimesDayToCache( $seckillcode, true );
		   	  //删除：获取用户当天分组下领取次数
		   	  self::setUserReceiveGroupTimesDayToCache( $SeckillIns->group_code, true );
		   	  // 更新数据
		   	  self::updateSeckillSuccessCount( $seckillcode );

		   	  return [0,'停止活动成功'];
	   }


	  



}
