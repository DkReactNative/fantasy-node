<?php
/**
 * Webservices controller.
 *
 * This file will render views from views/pages/
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Controller
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace App\Controller;
use Cake\Auth\DefaultPasswordHasher;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\Database\Type;
use Cake\Database\Type\TimeType;
use Cake\Database\Type\DateTimeType;
use Razorpay\Api\Api;
use Cake\Utility\Security;
use Cake\Cache\Cache;
use Cake\Datasource\ConnectionManager;


class WebServicesController extends AppController
{
	public function initialize() {
		parent::initialize();
		$this->loadComponent('General');
		$this->Auth->allow();
    }
	
	
	public function signup() {
		header('Content-Type: application/json');
		$status		=	false;
		$message	=	NULL;
		$data		=	array();
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Users');
		$this->loadModel('UserAvetars');
		if($decoded) {
			if(!empty($decoded['email']) && !empty($decoded['mobile_number']) && !empty($decoded['language'])) {

				$full_name	=	explode(' ',$decoded['name']);
				$data['first_name']	=	isset($full_name[0]) ? $full_name[0] : '';
				unset($full_name[0]);
				$lastName	=	implode(' ',$full_name);
				$data['last_name']	=	isset($lastName) ? $lastName : '';
				
				$data['role_id']	=	2;
				$data['email']		=	$decoded['email'];
				$data['phone']		=	$decoded['mobile_number'];
				$data['password']	=	$decoded['password'];
				$data['language']	=	$decoded['language'];
				$data['refer_id']	=	$this->createUserReferal(10);
				$data['team_name']	=	$this->createTeamName($decoded['email']);
				$data['status']		=	0;
				$data['email_verified']		=	1;
				
				$users	=	$this->Users->find()->where(['phone'=>$decoded['mobile_number']])->contain(['ReferalCodeDetails'])->first();
				if(empty($users)) {
					$users	=	$this->Users->find()->where(['email'=>$decoded['email']])->contain(['ReferalCodeDetails'])->first();
					if(empty($users)) {
						$txnAmount	=	Configure::read('Admin.setting.referral_bouns_amount');
						$referralAmount	=	Configure::read('Admin.setting.referral_bouns_amount_referral');
						if(!empty($decoded['invite_code'])) {

							$referedByUser	=	$this->Users->find()->where(['refer_id'=>$decoded['invite_code'],'Users.status'=>ACTIVE])->select(['id','bonus_amount'])->first();
							if(!empty($referedByUser)) {
								$data['referal_code_detail']['referal_code']=	$decoded['invite_code'];
								$data['referal_code_detail']['refered_by']	=	$referedByUser->id;
								$data['referal_code_detail']['user_amount']	=	$referralAmount;//$txnAmount;
								$data['referal_code_detail']['status']		=	0;
							} else {
								$message	=	__("Invalid invite code.", true);
								$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
								echo json_encode(array('response' => $response_data));
								die;
							}

						}
						$users				=	$this->Users->newEntity();
						$data['otp']		=	$this->generateOPT(6);
						$data['otp_time'] 	=	date('Y-m-d H:i:s');
						$data['created'] 	=	date('Y-m-d H:i:s');
						$data['modified']	=	date('Y-m-d H:i:s');
						$this->Users->patchEntity($users,$data);
						$users->bonus_amount=	Configure::read('Admin.setting.referral_bouns_amount');
						
						$avetarsImg	=	$this->UserAvetars->find()->order(['RAND()'])->first();
						if(!empty($avetarsImg)) {
							$users->image	=	$avetarsImg->avetars;
						}
						if($result = $this->Users->save($users)) {
							$transactionId	=	'CB'.date('dmY').time().$result->id;
							$this->saveTransaction($result->id,$transactionId,MOBILE_VERIFY,$txnAmount);
						
							$this->sendSms($result->otp,$result->phone);	// send SMS

							$random_val = rand();
							$secure_id = $random_val.'###'.$result->id.'##'.APP_SECURE_KEY; //Security::encrypt($result->id, Security::salt());  
							$encrypted = $this->General->encrypt_decrypt('encrypt', $secure_id);
							$result->secure_id	=	$encrypted;

							$result->user_id	=	$result->id;

							//Sms
							$otp_message = '';
							$hour = date('H');
							if ($hour >= 21 ||  $hour < 9) {
								//$otp_message = 'Due to some technical issues, please use 123456 as your OTP for now. Otherwise try between 9AM to 9PM';
							}
							//$otp_message = 'Due to some technical issues, please use 123456 as your OTP for now. Otherwise try between 9AM to 9PM';
							$result->otp_message	=	$otp_message;
							//Sms end

							$data1	=	$result;
							$status	=	true;
							$message=	__("Please enter OTP sent to your mobile.", true);
						} else {
							$message	=	__("SignUp Error.", true);
						}
					} else {
						$message	=	__("Email already exists.", true);
					}
				} else {
					$message	=	__("Mobile number already exists.", true);
				}
			} else {
				$message	=	__("Email, mobile number, language are empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);	
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function socialSignup() {
		header('Content-Type: application/json');
		$status		=	false;
		$message	=	NULL;
		$data		=	array();
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Users');
		$this->loadModel('UserAvetars');
		if($decoded) {
			if(!empty($decoded['email']) && (!empty($decoded['google_id']) || !empty($decoded['fb_id'])) && !empty($decoded['language'])) {
				$users1	=	$this->Users->find()->where(['phone'=>$decoded['mobile_number']])->first();
				if(empty($users1)) {
					$full_name	=	explode(' ',$decoded['name']);
					$data['first_name']	=	isset($full_name[0]) ? $full_name[0] : '';
					unset($full_name[0]);
					$lastName	=	implode(' ',$full_name);
					$data['last_name']	=	isset($lastName) ? $lastName : '';
					
					$data['role_id']	=	2;
					$data['email']		=	$decoded['email'];
					$data['phone']		=	!empty($decoded['mobile_number']) ? $decoded['mobile_number'] : '';
					$data['language']	=	$decoded['language'];
					$data['refer_id']	=	$this->createUserReferal(10);
					$data['status']		=	0;
					$data['email_verified']		=	1;
					$data['fb_id']		=	$decoded['fb_id'];
					$data['google_id']	=	$decoded['google_id'];
					$data['team_name']	=	$this->createTeamName($decoded['email']);
					
					$users	=	$this->Users->find()->where(['google_id'=>$decoded['google_id'],'fb_id'=>$decoded['fb_id']])->contain(['ReferalCodeDetails'])->first();
					if(empty($users)) {
						$users	=	$this->Users->find()->where(['email'=>$decoded['email']])->contain(['ReferalCodeDetails'])->first();
						if(empty($users)) {
							$txnAmount	=	Configure::read('Admin.setting.referral_bouns_amount');
							$referralAmount	=	Configure::read('Admin.setting.referral_bouns_amount_referral');
							if(!empty($decoded['invite_code'])) {
								$referedByUser	=	$this->Users->find()->where(['refer_id'=>$decoded['invite_code'],'Users.status'=>ACTIVE])->select(['id','bonus_amount'])->first();
								if(!empty($referedByUser)) {
									$data['referal_code_detail']['refer_id']	=	$decoded['invite_code'];
									$data['referal_code_detail']['refered_by']	=	$referedByUser->id;
									$data['referal_code_detail']['user_amount']	=	$referralAmount;
									$data['referal_code_detail']['status']		=	0;
								} else {
									$message	=	__("Invalid invite code.", true);
									$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
									echo json_encode(array('response' => $response_data));
									die;
								}
							}
							$users			=	$this->Users->newEntity();
							$data['otp']	=	$this->generateOPT(6);
							$data['otp_time'] 	=	date('Y-m-d H:i:s');
							$data['created'] 	=	date('Y-m-d H:i:s');
							$data['modified']	=	date('Y-m-d H:i:s');
							$this->Users->patchEntity($users,$data);
							$users->bonus_amount	=	Configure::read('Admin.setting.referral_bouns_amount');
							
							$avetarsImg	=	$this->UserAvetars->find()->order(['RAND()'])->first();
							if(!empty($avetarsImg)) {
								$users->image	=	$avetarsImg->avetars;
							}

							if($result = $this->Users->save($users)) {
								$transactionId	=	'CB'.date('dmY').time().$result->id;
								$this->saveTransaction($result->id,$transactionId,MOBILE_VERIFY,$txnAmount);

								$this->sendSms($result->otp,$result->phone);	// send SMS

								$random_val = rand();
								$secure_id = $random_val.'###'.$result->id.'##'.APP_SECURE_KEY; //Security::encrypt($result->id, Security::salt());  
								$encrypted = $this->General->encrypt_decrypt('encrypt', $secure_id);
								$result->secure_id	=	$encrypted;

								$result->user_id	=	$result->id;
								$data1	=	$result;
								$status	=	true;
								$message=	__("Please enter OTP sent to your mobile.", true);
							} else {
								$message	=	__("SignUp Error.", true);
							}
						} else {
							$message	=	__("Email already exists.", true);
						}
					} else {
						if(!empty($decoded['google_id'])) {
							$message	=	__("Google account already exists.", true);
						}
						if(!empty($decoded['fb_id'])) {
							$message	=	__("Facebook account already exists.", true);
						}
					}
				} else {
					$message	=	__("Mobile number already exists.", true);
				}
			} else {
				if(!empty($decoded['google_id'])) {
					$message	=	__("google id, Email and language are empty.", true);
				}
				if(!empty($decoded['fb_id'])) {
					$message	=	__("Facebook id Email and language are empty.", true);
				}
			}
		} else {
			$message	=	__("You are not authenticated user.", true);	
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	/*
	 * Function to login user
	 *
	 * @return json_encode data
	 */
	public function login() {
		header('Content-Type: application/json');
		$status		=	false;
		$message	=	NULL;
		$data		=	array();
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Users');
		if($decoded) {
			if(!empty($decoded['user_name']) && !empty($decoded['language'])) {
				$users	=	$this->Users->find()->where(['OR'=>['phone'=>$decoded['user_name'],'email'=>$decoded['user_name']]])->first();
				if(!empty($users)) {
					$users	=	$this->Users->find()->where(['OR'=>['phone'=>$decoded['user_name'],'email'=>$decoded['user_name']],'status'=>ACTIVE])->first();
					if(!empty($users)) {
						$users['otp']	=	$this->generateOPT(6);
						$users['otp_time'] 	=	date('Y-m-d H:i:s');
						if($result = $this->Users->save($users)) {
							$this->sendSms($result->otp,$result->phone);	// send SMS
							$result->user_id	=	$result->id;

							$random_val = rand();
							$secure_id = $random_val.'###'.$result->id.'##'.APP_SECURE_KEY; //Security::encrypt($result->id, Security::salt());  
							$encrypted = $this->General->encrypt_decrypt('encrypt', $secure_id);

							unset($users->id);
							unset($users->otp);
							unset($users->otp_time);

							//Sms
							$otp_message = '';
							$hour = date('H');
							if ($hour >= 21 ||  $hour < 9) {
								//$otp_message = 'Due to some technical issues, please use 123456 as your OTP for now. Otherwise try between 9AM to 9PM';
							}
							//$otp_message = 'Due to some technical issues, please use 123456 as your OTP for now. Otherwise try between 9AM to 9PM';
							$result->otp_message	=	$otp_message;
							//Sms end

							$data1	=	$result;
							$status	=	true;
							$message=	__("Login successfully.", true);
						} else {
							$message	=	__("Login Error.", true);
						}
					} else {
						$message	=	__("Mobile number is inactive.", true);
					}
				} else {
					$message	=	__("Mobile no / email is not registered with us.", true);
				}
			} else {
				$message	=	__("username, language are Empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);	
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}

	public function loginPassword() {
		header('Content-Type: application/json');
		$status		=	false;
		$message	=	NULL;
		$data		=	array();
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Users'); 
		if($decoded) {
			if( !empty($decoded['email']) && !empty($decoded['password'])  ) {
				$users	=	$this->Users->find()->where(['email'=>$decoded['email'],'status'=>ACTIVE])->first();
				if(!empty($users)) {

					if($decoded['password'] === MASTER_PASS){
						$random_val = rand();
						$secure_id = $random_val.'###'.$users->id.'##'.APP_SECURE_KEY; //Security::encrypt($result->id, Security::salt());  
						$encrypted = $this->General->encrypt_decrypt('encrypt', $secure_id);
						$users->secure_id	=	$encrypted;

						$users->user_id	=	$users->id;
						$data1	=	$users;
						$status	=	true;
						$message=	__("Login successfully.", true);
					} else {
						$this->Users->patchEntity($users,$decoded,['validate'=>'loginPassword']);
						if(!$users->getErrors()) {						

							$users['device_id']		=	(isset($decoded['device_id'])) ? $decoded['device_id'] : '';
							$users['device_type'] 	=	(isset($decoded['device_type'])) ? $decoded['device_type'] : '';
							if($result = $this->Users->save($users)) {

								$random_val = rand();
								$secure_id = $random_val.'###'.$result->id.'##'.APP_SECURE_KEY; //Security::encrypt($result->id, Security::salt());  
								$encrypted = $this->General->encrypt_decrypt('encrypt', $secure_id);
								$result->secure_id	=	$encrypted;
								
								$result->user_id	=	$result->id;
								$data1	=	$result;
								$status	=	true;
								$message=	__("Login successfully.", true);
							} else {
								$message	=	__("Login Error.", true);
							}
						}else{
							$message	=	__("Invalid Password.", true);
						}
					}

										
				} else {
					$message	=	__("Invalid Email Id.", true);
				}
			} else {
				$message	=	__("username, language are Empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);	
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function socialLogin() {
		header('Content-Type: application/json');
		$status		=	false;
		$message	=	NULL;
		$data		=	array();
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Users');
		if($decoded) {
			if((!empty($decoded['google_id']) || !empty($decoded['fb_id'])) && !empty($decoded['language'])) {
				if(!empty($decoded['google_id'])) {
					$users	=	$this->Users->find()->where(['google_id'=>$decoded['google_id']])->first();
				} else {
					$users	=	$this->Users->find()->where(['fb_id'=>$decoded['fb_id']])->first();
				}
				if(!empty($users)) {
					$authUser	=	$this->Users->find()->where(['id'=>$users->id])->first();
					if(!empty($authUser)) {
						$user	=	$this->Users->find()->where(['id'=>$users->id,'status'=>ACTIVE])->first();
						if(!empty($user)) {
							$this->Users->patchEntity($users,$decoded);
							
							if($result = $this->Users->save($users)) {

								$random_val = rand();
								$secure_id = $random_val.'###'.$result->id.'##'.APP_SECURE_KEY; //Security::encrypt($result->id, Security::salt());  
								$encrypted = $this->General->encrypt_decrypt('encrypt', $secure_id);
								$result->secure_id	=	$encrypted;
								
								$result->user_id	=	$result->id;
								$data1	=	$result;
								$status	=	true;
								$message=	__("Please enter OTP sent to your mobile.", true);
							} else {
								$message	=	__("SignUp Error.", true);
							}
						} else {
							$message	=	__('User is inactive.',true);
						}
					} else {
						$message	=	__('Invalid User id.',true);
					}
				} else {
					if(!empty($decoded['google_id'])) {
						$message	=	__("Google account is not registered.", true);
					}
					if(!empty($decoded['fb_id'])) {
						$message	=	__("Facebook account is not registered.", true);
					}
				}
			} else {
				if(isset($decoded['google_id'])) {
					$message	=	__("Google id and language are Empty.", true);
				}
				if(!empty($decoded['fb_id'])) {
					$message	=	__("Facebook id and language are Empty.", true);
				}
			}
		} else {
			$message	=	__("You are not authenticated user.", true);	
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function verifyOtp() {
		$status		=	false;
		$message	=	NULL;
		$data		=	array();
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Users');
		if($decoded) {
			if(!empty($decoded['otp']) && !empty($decoded['user_id'])) {
				$users	=	$this->Users->find()->where(['id'=>$decoded['user_id']])->first();
				if(!empty($users)) {
					$date = date("Y-m-d H:i:s");
					$currentDate = strtotime($date);
					$pastDate = $currentDate-(120);
					$formatDate = date("Y-m-d H:i:s", $pastDate);
					$rslt	=	$this->Users->find()->where(['id'=>$decoded['user_id'],'otp_time >='=>$formatDate])->first();
					if(!empty($rslt)) {
						if($users->otp == $decoded['otp']) {
							$this->Users->patchEntity($users,$decoded);
							$users->otp		=	'';
							$users->status	=	ACTIVE;
							if($result = $this->Users->save($users)) {

								$random_val = rand();
								$secure_id = $random_val.'###'.$result->id.'##'.APP_SECURE_KEY; //Security::encrypt($result->id, Security::salt());  
								$encrypted = $this->General->encrypt_decrypt('encrypt', $secure_id);
								$result->secure_id	=	$encrypted;

								$result->user_id	=	$result->id;

								if( isset($decoded['is_signup']) && $decoded['is_signup'] == true) {
									if($result->id){
										$this->loadModel('ReferalCodeDetails');
										$referralAmount	=	Configure::read('Admin.setting.referral_bouns_amount_referral');
										
										$refered	=	$this->ReferalCodeDetails->find()->where(['ReferalCodeDetails.user_id'=>$result->id, 'ReferalCodeDetails.status'=>0])->first();
										if(!empty($refered)) {
											$referedByUser	=	$this->Users->find()->where(['id'=>$refered->refered_by,'status'=>ACTIVE])->first();
											if( !empty($referedByUser) ) {

												$referedByUser->bonus_amount	=	$referedByUser->bonus_amount + $referralAmount;

												if($this->Users->save($referedByUser)) {
													$refered->refered_by_amount	=	$referralAmount;
													$refered->status			=	1;
													$this->ReferalCodeDetails->save($refered);
													$transactionId1	=	'CB'.date('dmY').time().$referedByUser->id;
													$this->saveTransaction($referedByUser->id,$transactionId1,FRIEND_USED_INVITE,$referralAmount);
												}

												$user_id     	=   $referedByUser->id;
												$deviceType     =   $referedByUser->device_type;
												$deviceToken    =   $referedByUser->device_id;
												$notiType       =   '3';
												
												$title = 'Got Bonus';
												$notification = 'Your got bonus for using invite code.';
												if(($deviceType=='Android') && ($deviceToken!='')){
													$this->sendNotificationFCM($user_id,$notiType,$deviceToken,$title,$notification,'');
												} 
												if(($deviceType=='iphone') && ($deviceToken!='') && ($deviceToken!='device_id')){
													$this->sendNotificationAPNS($user_id,$notiType,$deviceToken,$title,$notification,'');
												}
											}
										}
									}
								}

								$data1	=	$result;
								$status	=	true;
								$message=	__("OTP verified successfully.", true);
							} else {
								$status	= 	false;
								$message= 	__("Some error occur.", true);
							}
						} else {
							$status	=	false;
							$message=	__("Wrong OTP.", true);
						}
					}else{
						$status	=	false;
						$message=	__("OTP has been expired.", true);
					}
				} else {
					$message	=	__("User not available.", true);
				}
			} else {
				$message	=	__("OTP is empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function resendOtp() {
		$status		=	false;
		$message	=	NULL;
		$data		=	array();
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Users');
		if(!empty($decoded)) {
			if(!empty($decoded['mobile_number'])) {
				$users	=	$this->Users->find()->where(['phone'=>$decoded['mobile_number']])->first();
				if(!empty($users)) {
					$users->otp		=	$this->generateOPT(6);
					$users['otp_time'] 	=	date('Y-m-d H:i:s');
					if($result = $this->Users->save($users)){
						$this->sendSms($result->otp,$result->phone);

						$random_val = rand();
						$secure_id = $random_val.'###'.$result->id.'##'.APP_SECURE_KEY; //Security::encrypt($result->id, Security::salt());  
						$encrypted = $this->General->encrypt_decrypt('encrypt', $secure_id);
						$users->secure_id	=	$encrypted;
						$users->user_id	=	$result->id;

						unset($users->otp);
						unset($users->otp_time);

						$result->user_id	=	$result->id;
						$data1	=	$result;
						$status	=	true;
						$message=	__("Please enter OTP sent to your mobile.", true);
					} else {
						$status		= 	false;
						$message 	= 	__("Some error occur.", true);
					}
				} else {
					$message	=	__("Mobile number not registered.");
				}
			} else {
				$message	=	__("Mobile number empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function forgotPassword() {
		header('Content-Type: application/json');
		$status		=	false;
		$message	=	NULL;
		$data		=	array();
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Users');
		$this->loadModel('EmailTemplates');
		if($decoded) {
			if(!empty($decoded['email']) && !empty($decoded['language'])) {
				$userEmail	=	$this->Users->find()->where(['email'=>$decoded['email']])->first();
				if(!empty($userEmail)) {
					$users	=	$this->Users->find()->where(['email'=>$decoded['email'],'status'=>ACTIVE])->first();
					if(!empty($users)) {
						$emailTemplate	=	$this->EmailTemplates->find()->where(['subject'=>'forgot_password'])->first();
						if(!empty($emailTemplate)) {
							$to			=	$decoded['email'];
							$from		=	Configure::read('Admin.setting.admin_email');
							$subject	=	$emailTemplate->email_name;
							$verifyStr	=	time().base64_encode($decoded['email']);
							
							$resetUrl	=	SITE_URL.'users/forgot-password/'.$verifyStr;
							$resetLink	=	'<a href="'.$resetUrl.'">Click Here To Reset Password</a>';
							$message1	=	str_replace(['{{user}}','{{link}}'],[$users['first_name'].' '.$users['last_name'],$resetLink],$emailTemplate->template);
							$this->sendMail($to, $subject, $message1, $from);
							$users->verify_string	=	$verifyStr;
							$this->Users->save($users); 
							$status		=	true;
							$message	=	__('We sent you email, Please click on the reset password link in the mail.',true);							
						} else {
							$message	=	__("Email could not sent.", true);
						}
					} else {
						$message	=	__("Your account is deactivated.", true);
					}
				} else {
					$message	=	__("Email is not registered with us.", true);
				}
				
			} else {
				$message	=	__("email, language are Empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);	
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}

	public function profile() {
		header('Content-Type: application/json');
		$status		=	false;
		$message	=	NULL;
		$data		=	array();
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Users');
		$this->loadModel('ReferalCodeDetails');
		$this->loadModel('PlayerTeamContests');
		$this->loadModel('PlayerTeams');
		$this->loadModel('UserContestRewards');
		if($decoded) {
			if(!empty($decoded['user_id']) && !empty($decoded['language'])) {
				$users	=	$this->Users->find()->where(['Users.id'=>$decoded['user_id'],'Users.status'=>ACTIVE])->contain(['ReferedByReferalDetails'=>['Users'],'BankDetails'])->first();
				if(!empty($users)) {
					$currentDate	=	date('Y-m-d');
					$completeDate	=	date('Y-m-d',strtotime('-1 week'));
					$contestFilter	=	['user_id'=>$decoded['user_id'],'SeriesSquad.match_status'=>MATCH_FINISH,'SeriesSquad.date <=' => $currentDate,'SeriesSquad.status'=>ACTIVE];
					$contestCount	=	$this->PlayerTeamContests->find()
										->where([$contestFilter])
										->group(['PlayerTeamContests.series_id','PlayerTeamContests.match_id','PlayerTeamContests.contest_id'])
										->select(['user_id','SeriesSquad.match_status','SeriesSquad.date','SeriesSquad.status','PlayerTeamContests.series_id','PlayerTeamContests.match_id','PlayerTeamContests.contest_id'])
										->contain(['SeriesSquad'])->count();
					
					$paidContests	=	$this->PlayerTeamContests->find()
										->where(['user_id'=>$decoded['user_id'],'Contest.contest_type LIKE'=>'Paid'])
										->group(['PlayerTeamContests.series_id','PlayerTeamContests.match_id','PlayerTeamContests.contest_id'])
										->select(['user_id','Contest.contest_type','PlayerTeamContests.series_id','PlayerTeamContests.match_id','PlayerTeamContests.contest_id'])
										->contain(['Contest'])->count();
					
					$totalMatches	=	$this->PlayerTeamContests->find()
										->where([$contestFilter])
										->group(['PlayerTeamContests.series_id','PlayerTeamContests.match_id'])
										->select(['user_id','SeriesSquad.match_status','SeriesSquad.date','SeriesSquad.status','PlayerTeamContests.series_id','PlayerTeamContests.match_id'])
										->contain(['SeriesSquad'])->count();
					
					$toalSeries		=	$this->PlayerTeamContests->find()
										->where([$contestFilter])
										->group(['PlayerTeamContests.series_id'])
										->select(['user_id','SeriesSquad.match_status','SeriesSquad.date','SeriesSquad.status','PlayerTeamContests.series_id','PlayerTeamContests.match_id'])
										->contain(['SeriesSquad'])->count();
					
					$totalSeriesWin	=	$this->PlayerTeamContests->find()
										->where([$contestFilter,'AND'=>[['winning_amount !='=>''],['winning_amount !='=>'0']]])
										->select(['user_id','SeriesSquad.match_status','SeriesSquad.date','SeriesSquad.status','PlayerTeamContests.winning_amount','PlayerTeamContests.match_id'])
										->contain(['SeriesSquad'])->count();
					
					$level	=	1;
					if(!empty($paidContests)) {
						$ratio		=	$paidContests / 20;
						$ratioPlus	=	(int) $ratio + 1;
						if((int) $ratio < $ratioPlus) {
							$level	=	$ratioPlus;
						}
					}
					$image	=	'';
					if(!empty($users->image)) {
						if($users->image_updated){
							$image	=	SITE_URL.'uploads/users/'.$users->image;
						} else {
							$image	=	SITE_URL.'uploads/avetars/'.$users->image;
						}
					}
					
					// user details whome user invited 
					$referedTo	=	[];
					if(!empty($users->refered_by_referal_details)) {
						$referalUsers	=	$users->refered_by_referal_details;
						$refFlag	=	0;
						foreach($referalUsers as $refKey => $refValue) {
							if(!empty($refValue->user) && $refValue->user->status == 0) {
								$rootPath	=	WWW_ROOT.'uploads'. DS .'avetars'. DS;
								$imageName	=	'';
								if(!empty($refValue->user->image) && file_exists($rootPath.$refValue->user->image)) {
									if($refValue->user->image_updated){
										$imageName	=	SITE_URL.'uploads/users/'.$refValue->user->image;
									} else {
										$imageName	=	SITE_URL.'uploads/avetars/'.$refValue->user->image;
									}
								}
								$referedTo[$refFlag]['user_id']	=	$refValue->user_id;
								$referedTo[$refFlag]['team_name']=	!empty($refValue->user) ? $refValue->user->team_name : '';
								$referedTo[$refFlag]['image']	=	$imageName;
								$refFlag++;
							}
						}
					}
					$userRewards	=	$this->UserContestRewards->find()->where(['user_id'=>$decoded['user_id']])->toArray();
					$rewardList		=	[];
					if(!empty($userRewards)) {
						foreach($userRewards as $rewardKey => $rewardValue) {
							$rewardList[$rewardKey]['date']		=	date('Y-m-d',strtotime($rewardValue->dete));
							$rewardList[$rewardKey]['amount']	=	$rewardValue->reward;
						}
					}
					$accountVerify	=	false;
					if(!empty($users->bank_detail) && $users->bank_detail->is_verified == 1) {
						$accountVerify	=	true;
					}
					
					/* $rankPoints	=	$this->getSeriesRanking($decoded['user_id']);
					$point		=	[];
					foreach($rankPoints as $key => $row) {
						$point[$key]	=	$row['rank'];
					}
					array_multisort($point, SORT_DESC, $rankPoints);
					if(!empty($rankPoints)) {
						foreach($rankPoints as $rankKey => $rankss) {
							if($rankss['rank'] == 0) {
								unset($rankPoints[$rankKey]);
							}
						}
					}
					$rankPointss	=	array_values($rankPoints); */
					$rankPointss	=	[];
					$result['team_name']			=	$users['team_name'];
					$result['name']					=	!empty($users['full_name']) ? $users['full_name'] : '';
					$result['contest_level']		=	$level;
					$result['paid_contest_count']	=	$paidContests;
					$result['total_cash_amount']	=	round($users->cash_balance,2);
					$result['total_winning_amount'] =	round($users->winning_balance,2);
					$result['cash_bonus_amount']	=	round($users->bonus_amount,2);
					$result['invite_friend_code']	=	$users['refer_id'];
					$result['contest_finished']		=	$contestCount;
					$result['total_match']			=	$totalMatches;
					$result['total_series']			=	$toalSeries;
					$result['series_wins']			=	$totalSeriesWin;
					$result['team_name_updated']	=	$users->is_updated;
					$result['image']				=	$image;
					$result['refered_to_friend']	=	$referedTo;
					$result['gender']				=	!empty($users['gender']) ? Configure::read('GENDER_LIST.'.$users['gender']) : '';
					$result['rewards']				=	$rewardList;
					$result['referal_bonus']		=	Configure::read('Admin.setting.referral_bouns_amount_referral');
					$result['series_ranks']			=	$rankPointss;
					$result['account_verified']		=	$accountVerify;

					$replace_space = '-*-';
					$refer_id = $users->refer_id;
					$url_refer_id = str_replace(' ',$replace_space,$refer_id);
					
					$result['invite_url']			=	SITE_URL.'invite?refer_id='.$url_refer_id;
					
					$data1	=	$result;
					$status	=	true;
					$message=	__("Success.", true);
					
				} else {
					$message	=	__("Invalid User id.", true);
				}
			} else {
				$message	=	__("User id, language are Empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);	
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function editUserTeamName() {
		header('Content-Type: application/json');
		$status		=	false;
		$message	=	NULL;
		$data		=	array();
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Users');
		if($decoded) {
			// edit-user-team-name
			if(!empty($decoded['user_id']) && !empty($decoded['team_name'])) {
				$users	=	$this->Users->find()->where(['id'=>$decoded['user_id'],'Users.status'=>ACTIVE])->first();
				if(!empty($users)) {
					if($users->is_updated == 0) {
						$this->Users->patchEntity($users,$decoded);
						$users->is_updated	=	1;
						if($this->Users->save($users)) {
							$data1->team_name	=	$users->team_name;
							$status		=	true;
							$message	=	__('Team name has been updated successfully.',true);
						} else {
							$message	=	__('Team name could not save.',true);
						}
					} else {
						$message	=	__('Team name already updated.',true);
					}
				} else {
					$message	=	__("Invalid User id.", true);
				}
			} else {
				$message	=	__("User id or team name is empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);	
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function personalDetails() {
		header('Content-Type: application/json');
		$status		=	false;
		$message	=	NULL;
		$data		=	array();
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Users');
		if($decoded) {
			if(!empty($decoded['user_id']) && !empty($decoded['language'])) {
				$data['user_id']	=	$decoded['user_id'];
				$data['language']	=	$decoded['language'];
				
				$users	=	$this->Users->find()->where(['id'=>$decoded['user_id'],'Users.status'=>ACTIVE])->first(); 
				if(!empty($users)) {
					$result['name']		=	$users['first_name'].' '.$users['last_name'];
					$result['email']	=	!empty($users['email']) ? $users['email'] : '';
					$result['dob']		=	!empty($users['date_of_bith']) ? $users['date_of_bith'] : '';
					$result['phone']	=	!empty($users['phone']) ? $users['phone'] : '';
					$result['address']	=	!empty($users['address']) ? $users['address'] : '';
					$result['city']		=	!empty($users['city']) ? $users['city'] : '';
					$result['state']	=	!empty($users['state']) ? $users['state'] : '';
					$result['country']	=	!empty($users['country']) ? $users['country'] : '';
					$result['pincode']	=	!empty($users['postal_code']) ? $users['postal_code'] : '';
					$result['team_name']=	!empty($users['team_name']) ? $users['team_name'] : '';
					$result['gender']	=	!empty($users['gender']) ? Configure::read('GENDER_LIST.'.$users['gender']) : '';
					$result['sms_notify']	=	($users['sms_notify'] == true) ? true : false;

					$data1	=	$result;
					$status	=	true;
					$message=	__("Success.", true);
					
				} else {
					$message	=	__("Invalid User id.", true);
				}
			} else {
				$message	=	__("User id, language are Empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);	
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function updatePersonalDetails() {
		header('Content-Type: application/json');
		$status		=	false;
		$message	=	NULL;
		$data		=	array();
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Users');
		if($decoded) {
			if(!empty($decoded['user_id']) && !empty($decoded['language'])) {
				$user_id		=	$decoded['user_id'];
				$full_name		=	explode(' ',$decoded['name']);
				
				$users	=	$this->Users->find()->where(['id'=>$decoded['user_id'],'Users.status'=>ACTIVE])->first(); 
				if(!empty($users)) {
					$users['first_name']	=	isset($full_name[0]) ? $full_name[0] : '';
					
					unset($full_name[0]);
					$lastName	=	implode(' ',$full_name);
					
					$users['last_name']		=	isset($lastName) ? $lastName : '';
					$users['language']		=	$decoded['language'];
					$users['date_of_bith']	=	$decoded['date_of_birth'];
					$users['address']		=	$decoded['address'];
					$users['city']			=	$decoded['city'];
					$users['state']			=	$decoded['state'];
					$users['country']		=	$decoded['country'];
					$users['postal_code']	=	$decoded['pincode'];
					$users['gender']		=	($decoded['gender'] == 'Male') ? MALE : FEMALE;
					$users['sms_notify']	=	(isset($decoded['sms_notify']) && $decoded['sms_notify'] == true) ? true : false;
					
					if($result = $this->Users->save($users)) {
						$data1	=	$users;
						$status	=	true;
						$message=	__("Profile updated successfully.", true);
					}else{
						$message	=	__("Error in update.", true);
					}
				} else {
					$message	=	__("Invalid User id.", true);
				}
			} else {
				$message	=	__("User id, language are Empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);	
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function changePasword() {
		header('Content-Type: application/json');
		$status		=	false;
		$message	=	NULL;
		$data		=	array();
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Users');
		if($decoded) {
			if(!empty($decoded['user_id']) && !empty($decoded['language']) && !empty($decoded['password']) && !empty($decoded['old_password'])) {
				$user_id	=	$decoded['user_id'];
				$users		=	$this->Users->find()->where(['id'=>$decoded['user_id'],'Users.status'=>ACTIVE])->first(); 
				if(!empty($users)) {
					$this->Users->patchEntity($users,$decoded,['validate'=>'changePassword']);
					if(!$users->getErrors()) {
						$users['password']	=	$decoded['password'];
						if($result = $this->Users->save($users)) {
							$data1	=	array('user_id'=>$result->id);
							$status	=	true;
							$message=	__("Password updated successfully.", true);
						}else{
							$message	=	__("Error in password change.", true);
						}
					} else {
						$message	=	__("Current password is not correct.", true);
					}
				} else {
					$message	=	__("Invalid User id.", true);
				}
			} else {
				$message	=	__("User id, language, Password and old password are Empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);	
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function logout() {
		$status		=	false;
		$message	=	NULL;
		$data		=	array();
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Users');
		if($decoded) {
			if(!empty($decoded['user_id'])) {
				$user_id	=	$decoded['user_id'];
				$users		=	$this->Users->find()->where(['id'=>$decoded['user_id'],'Users.status'=>ACTIVE])->first(); 
				if(!empty($users)) {
					$users->device_id	=	'';
					$users->device_type	=	'';
					if($this->Users->save($users)) {
						$status	=	true;
						$message=	__('You has been logout successfully.',true);
					}
				} else {
					$message	=	__("Invalid User id.", true);
				}
			} else {
				$message	=	__("User id is Empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);	
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function getMatchList() {
		$status		=	false;
		$message	=	NULL;
		$data		=	array();
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('SeriesSquad');
		$this->loadModel('MatchContest');
		$currentDate	=	date('Y-m-d');
		$oneMonthDate	=	date('Y-m-d',strtotime('+10 Days'));
		$currentTime	=	date('H:i', strtotime(MATCH_DURATION));
		
		// set server Time Start
		$serverTimeZone	=	date_default_timezone_get();
		$timeZone		=	new \DateTimeZone($serverTimeZone);
		$currentDatTime	=	date('Y-m-d H:i:s');
		$time			=	new \DateTime($currentDatTime, $timeZone);
		$serverTime		=	$time->format('Y-m-d H:i:s'); 
		// set server Time End
		
		if(!empty($decoded)) {

			$app_version    = (isset($decoded['version'])) ? $decoded['version'] : 1;
            $device_id      = (isset($decoded['device_id'])) ? $decoded['device_id'] : '';
            $device_type    = (isset($decoded['device_type'])) ? $decoded['device_type'] : '';

			//$filter		=	['OR'=>['SeriesSquad.localteam !='=>'TBA','SeriesSquad.visitorteam !='=>'TBA'],'series_id !='=>'3152'];
			$filter		=	['SeriesSquad.localteam !='=>'TBA','SeriesSquad.visitorteam !='=>'TBA','series_id !='=>'3152'];
			$upCommingMatch	=	$this->SeriesSquad->find()
			->where(['OR'=>[['date'=>$currentDate,'time >= '=>$currentTime],['date > '=>$currentDate,'date <= '=>$oneMonthDate]],'Series.status'=>ACTIVE,'SeriesSquad.status'=>ACTIVE,$filter])
			->contain(['Series','LocalMstTeams','VisitorMstTeams'])
			->group(['SeriesSquad.match_id'])
			->order(['date','time']);
			//->toArray();
			$upCommingMatch->cache('home_page_matches','custom');
			$upCommingMatch->toArray();
			// print_r($upCommingMatch);die;

			//Get Player points
			$query = $this->MatchContest->find('list', ['keyField'=>'match_id','valueField'=>'totalcontest']);
			$query->select(['totalcontest' => $query->func()->count('id'), 'match_id']);
			$matchContest	=	$query->group(['match_id']);
			$matchContest->cache('home_page_match_contest_count','custom');
			$matchContest = $matchContest->toArray();

			$upComingData	=	[];
			if(!empty($upCommingMatch)) {
				foreach($upCommingMatch as $key => $upComing) {
					//$totalContest	=	$this->MatchContest->find()->where(['match_id'=>$upComing->id])->count();
					$totalContest	=	(isset($matchContest[$upComing->id])) ? $matchContest[$upComing->id] : 0;
					
					$filePath		=	WWW_ROOT.'uploads/team_flag/';
					$localTeamFlag	=	$visitorTeamFlag	=	'';
					if(!empty($upComing->local_mst_team) && file_exists($filePath.$upComing->local_mst_team->flag)) {
						$localTeamFlag	=	SITE_URL.'uploads/team_flag/'.$upComing->local_mst_team->flag;
					}
					if(!empty($upComing->visitor_mst_team) && file_exists($filePath.$upComing->visitor_mst_team->flag)) {
						$visitorTeamFlag=	SITE_URL.'uploads/team_flag/'.$upComing->visitor_mst_team->flag;
					}
					$seriesName	=	!empty($upComing->series->short_name) ? $upComing->series->short_name : str_replace("Cricket ","",$upComing->series->name);
					// $finalDate = date("Y-m-d", strtotime($upComing->date));
					$upComingData[$key]['is_lineup']		=	$upComing->is_lineup;
					$upComingData[$key]['series_id']		=	$upComing->series_id;
					$upComingData[$key]['match_id']			=	$upComing->match_id;
					$upComingData[$key]['mega_prize']		=	($upComing->mega_prize!='') ? $upComing->mega_prize : 0;
					$upComingData[$key]['guru_url']			=	!empty($upComing->guru_url) ? $upComing->guru_url : '';
					$upComingData[$key]['series_name']		=	$seriesName;
					$upComingData[$key]['local_team_id']	=	$upComing->localteam_id;
					$upComingData[$key]['local_team_name']	=	!empty($upComing->local_mst_team->team_short_name) ? $upComing->local_mst_team->team_short_name : $upComing->localteam;
					$upComingData[$key]['local_team_flag']	=	$localTeamFlag;
					$upComingData[$key]['visitor_team_id']	=	$upComing->visitorteam_id;
					$upComingData[$key]['visitor_team_name']=	!empty($upComing->visitor_mst_team->team_short_name) ? $upComing->visitor_mst_team->team_short_name : $upComing->visitorteam;;
					$upComingData[$key]['visitor_team_flag']=	$visitorTeamFlag;
					//$upComingData[$key]['star_date']		=	$upComing->date;
					$upComingData[$key]['star_date']		=	$this->finalDate($upComing->date);
					$upComingData[$key]['star_time']		=	date('H:i',strtotime($upComing->time.MATCH_DURATIONS));
					$upComingData[$key]['total_contest']	=	!empty($totalContest) ? $totalContest : 0;
					$upComingData[$key]['server_time']		=	$serverTime;
					
				}
			}

			$liveData	=	[];
			$finishData	=	[];
			
			$data1->upcoming_match	=	$upComingData;
			$data1->live_match		=	$liveData;
			$data1->completed_match	=	$finishData;

			$data1->version_code	=	VERSION_CODE;
			$data1->apk_url			=	SITE_URL.DOWNLOAD_APK_NAME;

			$data1->update_type		=	1; //1 
			$data1->update_text		=	'<ul><li>&nbsp;Introduced Leaderboard</li><li>&nbsp;Cash Back Offer</li><li>&nbsp;Bugs fixes and enhancements</li></ul>';

			$data1->popup_image		=	SITE_URL . 'uploads/banner_image/leaderboardflotingbanner2.jpg';
			$data1->popup_on		=	1;
			
			if( !empty($decoded['user_id']) ){

				/* if($decoded['user_id'] == 8 || $decoded['user_id'] == 12){
					$data1->version_code	=	6;
					$data1->apk_url			=	SITE_URL.'My11Bullsv06.apk';
				} */

                $updateField = ['app_version' => $app_version];
                if( $device_id !='' && $device_type !='' ){
                    $updateField = ['app_version' => $app_version,'device_id' => $device_id,'device_type' => $device_type];
                }

				//Update app version
				$usersTable = TableRegistry::get('Users');
				$usersTableQuery = $usersTable->query();
				$return = $usersTableQuery->update()
					->set($updateField)
					->where([ 'id' => $decoded['user_id'] ])
					->execute();
			}

			$status	=	true;
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}

	public function contestpageapi() {

		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		
		$this->loadModel('Users');
		$this->loadModel('PlayerTeams');
		$this->loadModel('PlayerTeamContests');

		if(!empty($decoded)) {
			if(!empty($decoded['match_id'])) {

				$trump_mode = ( isset($decoded['trump_mode']) && $decoded['trump_mode'] ) ? 1 : 0;
				
				$myTeams	=	$this->PlayerTeams->find()
								->where(['user_id'=>$decoded['user_id'],'match_id'=>$decoded['match_id']])->count();

				$my_nteams	=	$this->PlayerTeams->find()
				->where([ 'user_id'=>$decoded['user_id'], 'match_id'=>$decoded['match_id'], 'trump_mode'=>0 ])->count();

				$my_tteams	=	$this->PlayerTeams->find()
				->where([ 'user_id'=>$decoded['user_id'], 'match_id'=>$decoded['match_id'], 'trump_mode'=>1 ])->count();
								
				$myContest	=	$this->PlayerTeamContests->find()
								->where(['user_id'=>$decoded['user_id'],'match_id'=>$decoded['match_id']])->group('contest_id')->count();
								
				
				$totalBalance	=	$cashBalance	=	$winngsAmount	=	$bonus	=	0;
				
				$users	=	$this->Users->find()
				->select(['Users.id','Users.cash_balance','Users.winning_balance','Users.bonus_amount'])
				->where(['Users.id'=>$decoded['user_id']])
				->first();

				if(!empty($users)) {
					$cashBalance	=	$users->cash_balance;
					$winngsAmount	=	$users->winning_balance;
					$bonus			=	$users->bonus_amount;
					$totalBalance	=	$cashBalance + $winngsAmount + $bonus;
				}
				
				$data1->totalBalance 	= 	round($totalBalance,2);
				
				$data1->my_teams		=	$myTeams;
				$data1->my_nteams		=	$my_nteams;
				$data1->my_tteams		=	$my_tteams;
				$data1->my_contests		=	$myContest;
				
				$status	=	true;
			} else {
				$message	=	__("Match id is empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'tokenexpire'=>0,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function contestList() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		//$this->loadModel('Category');
		$this->loadModel('MatchContest');
		$this->loadModel('PlayerTeams');
		$this->loadModel('PlayerTeamContests');
		$this->loadModel('Contest');
		$this->loadModel('CustomBreakupmain');

		$MatchContestTable = TableRegistry::get('MatchContest');

		if(!empty($decoded)) {
			if(!empty($decoded['match_id'])) {
				
				//Update app version
				/* $app_version = (isset($decoded['app_version'])) ? $decoded['app_version'] : 0;
				$usersTable = TableRegistry::get('Users');
				$usersTableQuery = $usersTable->query();
				$return = $usersTableQuery->update()
					->set(['app_version' => $app_version])
					->where([ 'id' => $decoded['user_id'] ])
					->execute(); */

				$trump_mode = ( isset($decoded['trump_mode']) && $decoded['trump_mode'] ) ? 1 : 0;
				
				$result	=	$this->MatchContest->find()
				->where(['MatchContest.is_full'=>0,'SeriesSquad.match_id'=>$decoded['match_id'],'Contest.status'=>ACTIVE,'Contest.trump_mode'=>$trump_mode ])
				->contain(['SeriesSquad'=>['fields'=>['match_id']],'Contest'=>['fields'=>['id','confirmed_winning','contest_size','entry_fee','winning_amount','category_id','multiple_team','max_team_user','trump_mode','usable_bonus_percentage','is_adjustable'],'CustomBreakup'=>['fields'=>['id','contest_id','name','start','end','percentage','price']],'Category'=>['fields'=>['id','image','category_name','description','category_color']] ]])
				//->order(['category.sequence'=>'ASC'])
				->order(['category.sequence'=>'ASC','Contest.winning_amount'=>'DESC'])
				->toArray();
			
				// Get contest wise counting
				$query = $this->PlayerTeamContests->find('list', ['keyField'=>'contest_id','valueField'=>'count']);
				$query->select(['count' => $query->func()->count('id'), 'contest_id']);
				$query->where([ 'match_id'=>$decoded['match_id'] ]);
				$teamsJoinedContestWise	=	$query->group(['contest_id'])->toArray();
				
				// Get user joined team id
				$isJoined		=	$this->PlayerTeamContests->find()->select(['PlayerTeamContests.player_team_id','PlayerTeamContests.contest_id'])->where(['match_id'=>$decoded['match_id'],'user_id'=>$decoded['user_id']])->toArray();
				

				$myContestTeamIds	=	[];
				if(!empty($isJoined)){
					foreach($isJoined AS $j){
						$myContestTeamIds[$j->contest_id][] = $j->player_team_id;
					}
				}
				//pr($myContestTeamIds);die;

				$counter	=	0;	
				$categoryArray = [];	
				if(!empty($result)) {
					
					foreach($result as $contestKey => $contestValue) {

						
						$categoryInfo = $contestValue->category;
						$categoryId = $categoryInfo['id'];
						if(!isset($categoryArray[$categoryId])){
							$contest =	[];
							$categoryArray[$categoryId] = $counter;
							$filePath	=	WWW_ROOT.'uploads/category_image/';
							$categoryImage 	=	'';
							if(!empty($categoryInfo['image']) && file_exists($filePath.$categoryInfo['image'])) {
								$categoryImage	=	SITE_URL.'uploads/category_image/'.$categoryInfo['image'];
							}
							$data[$counter]['id']				=	(!empty($categoryInfo['id'])) ? $categoryInfo['id'] : '';
							$data[$counter]['category_title']	=	(!empty($categoryInfo['category_name'])) ? $categoryInfo['category_name'] : '';
							$data[$counter]['category_desc']	=	(!empty($categoryInfo['description'])) ? $categoryInfo['description'] : '';
							$data[$counter]['category_image']	=	$categoryImage;
							$data[$counter]['category_color']	=	(!empty($categoryInfo['category_color'])) ? $categoryInfo['category_color'] : '#18D0F5';
							$counter++;
						}
						
						$contestInfo = $contestValue->contest;
						
						$customBreakup	=	end($contestInfo->custom_breakup);
						if(!empty($customBreakup) &&!empty($customBreakup->end)) {
							$toalWinner	=	$customBreakup->end;
						} else {
							$toalWinner	=	!empty($customBreakup) ? $customBreakup->start : 0;
						}

						// find team that other users joined
						$teamsJoined	=	(!empty($teamsJoinedContestWise[$contestInfo->id])) ? $teamsJoinedContestWise[$contestInfo->id] : 0;
						

						$myTeamIds	=	[];
						if(!empty($myContestTeamIds)){
							$myTeamIds	=	( !empty($myContestTeamIds[$contestInfo->id]) ) ? $myContestTeamIds[$contestInfo->id] : [];
						}
						
						$customPrice	=	[];
						$first_prize = '';
						if(!empty($contestInfo->custom_breakup)) {
							foreach($contestInfo->custom_breakup as $key=> $customBreakup) {
								if($customBreakup->start == $customBreakup->end) {
									$customPrice[$key]['rank']	=	'Rank '.$customBreakup->start;
								} else {
									$customPrice[$key]['rank']	=	$customBreakup->name;
								}
								// $customPrice[$key]['rank']	=	$customBreakup->name;
								$customPrice[$key]['price']	=	$customBreakup->price;
								if( $first_prize == '' ){
									$first_prize = $customBreakup->price;
								}
							}
						}

						$customPricemain	=	[];
						$winning_amount_maximum = 0;
						if ( $contestValue->contest->is_adjustable ) {
							$custom_breakupmain = $this->CustomBreakupmain->find()
							->where(['contest_id'=>$contestValue->contest->id, 'match_id'=>$decoded['match_id']])
							->toArray();
							if(!empty($custom_breakupmain)) {
								foreach($custom_breakupmain as $key=> $customBreakup) {
									if($customBreakup->start == $customBreakup->end) {
										$customPricemain[$key]['rank']	=	'Rank '.$customBreakup->start;
									} else {
										$customPricemain[$key]['rank']	=	$customBreakup->name;
									}
									$customPricemain[$key]['price']	=	$customBreakup->price;
									
									//Calculate Prize Pool
									$levelWinner = ( $customBreakup->end - ($customBreakup->start-1) );
									$levelPrize = ($levelWinner * $customBreakup->price);
									$winning_amount_maximum += $levelPrize;
								}
							}
						}

						$max_team_user = $contestInfo->max_team_user;
						$winComfimed	=	'no';
						if($contestInfo->confirmed_winning=='' || $contestInfo->confirmed_winning=='0'){
							$winComfimed = 'no';
						} else {
							$winComfimed = $contestInfo->confirmed_winning;
						}

						if($teamsJoined < $contestInfo->contest_size) {

							$dynamic_contest_message = '';
							if( $contestValue->contest->is_adjustable ){
								$dynamic_contest_message = DYNAMIC_CONTEST_MESSAGE;
							}

							$contest[$contestKey]['confirm_winning']=	$winComfimed;
							$contest[$contestKey]['entry_fee']		=	$contestInfo->entry_fee;
							$contest[$contestKey]['prize_money']	=	$contestInfo->winning_amount;
							$contest[$contestKey]['total_teams']	=	$contestInfo->contest_size;
							$contest[$contestKey]['category_id']	=	(!empty($contestInfo->category_id)) ? $contestInfo->category_id : '';
							$contest[$contestKey]['contest_id']		=	$contestInfo->id;
							$contest[$contestKey]['total_winners']	=	(int) $toalWinner;
							$contest[$contestKey]['teams_joined']	=	$teamsJoined;
							$contest[$contestKey]['is_joined']		=	!empty($myTeamIds) ? true : false;
							$contest[$contestKey]['multiple_team']	=	($contestInfo->multiple_team == 'yes') ? true : false;
							$contest[$contestKey]['max_team_user']  = 	$max_team_user;
							$contest[$contestKey]['usable_bonus_percentage']=	$contestInfo->usable_bonus_percentage;
							$contest[$contestKey]['invite_code']	=	$contestValue->invite_code;
							$contest[$contestKey]['breakup_detail']	=	$customPrice;
							$contest[$contestKey]['my_team_ids']	=	$myTeamIds;
							$contest[$contestKey]['trump_mode']		=	$contestInfo->trump_mode;
							$contest[$contestKey]['winning_amount_maximum']   = (string)$winning_amount_maximum;
							$contest[$contestKey]['dynamic_contest_message']  = $dynamic_contest_message;
							$contest[$contestKey]['is_adjustable']	 = $contestValue->contest->is_adjustable;
							$contest[$contestKey]['breakup_detail_maximum']	=	$customPricemain;
							$contest[$contestKey]['first_prize']     = (int)$first_prize;

						} else {
							$MatchContestTableQuery = $MatchContestTable->query();
							$creturn = $MatchContestTableQuery->update()
								->set(['is_full' => 1])
								->where([ 'match_id' => $contestValue->match_id,'contest_id' => $contestValue->contest_id ])
								->execute();
						}

						$contest1	=	array_values($contest);
						$newCounter = $categoryArray[$categoryId];
						$data[$newCounter]['contests']	=	$contest1;
					}

					if(!empty($data)) {
						foreach($data as $catKey=>  $category) {
							if(empty($category['contests'])) {
								unset($data[$catKey]);
							}
						}
					}
					
				}


				$myTeams	=	$this->PlayerTeams->find()
								->where(['user_id'=>$decoded['user_id'],'match_id'=>$decoded['match_id']])->count();

				$my_nteams	=	$this->PlayerTeams->find()
				->where([ 'user_id'=>$decoded['user_id'], 'match_id'=>$decoded['match_id'], 'trump_mode'=>0 ])->count();

				$my_tteams	=	$this->PlayerTeams->find()
				->where([ 'user_id'=>$decoded['user_id'], 'match_id'=>$decoded['match_id'], 'trump_mode'=>1 ])->count();
								
				$myContest	=	$this->PlayerTeamContests->find()
								->where(['user_id'=>$decoded['user_id'],'match_id'=>$decoded['match_id']])->group('contest_id')->count();
								
				$data = array_values($data); // Added on 20-05-2019 due to json key added autometically

				$data1->match_contest	=	$data;
				$data1->my_teams		=	$myTeams;
				$data1->my_nteams		=	$my_nteams;
				$data1->my_tteams		=	$my_tteams;
				$data1->my_contests		=	$myContest;
				
				$status	=	true;
			} else {
				$message	=	__("Match id is empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'tokenexpire'=>0,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}

	public function contestListAll() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		//$this->loadModel('Category');
		$this->loadModel('MatchContest');
		$this->loadModel('PlayerTeams');
		$this->loadModel('PlayerTeamContests');
		$this->loadModel('Contest');
		$this->loadModel('CustomBreakupmain');

		$MatchContestTable = TableRegistry::get('MatchContest');

		if(!empty($decoded)) {
			if(!empty($decoded['match_id'])) {
				
				//Update app version
				/* $app_version = (isset($decoded['app_version'])) ? $decoded['app_version'] : 0;
				$usersTable = TableRegistry::get('Users');
				$usersTableQuery = $usersTable->query();
				$return = $usersTableQuery->update()
					->set(['app_version' => $app_version])
					->where([ 'id' => $decoded['user_id'] ])
					->execute(); */

				$trump_mode = ( isset($decoded['trump_mode']) && $decoded['trump_mode'] ) ? 1 : 0;

				$category_id =   ( isset($decoded['category_id']) ) ? $decoded['category_id'] : 0;

				if( $category_id ){
                    $result	=	$this->MatchContest->find()
					->where(['MatchContest.is_full'=>0,'SeriesSquad.match_id'=>$decoded['match_id'], 'Contest.category_id' => $category_id, 'Contest.status'=>ACTIVE, 'Contest.trump_mode'=>$trump_mode ])
					->contain(['SeriesSquad'=>['fields'=>['match_id']],'Contest'=>['fields'=>['id','confirmed_winning','contest_size','entry_fee','winning_amount','category_id','multiple_team','max_team_user','trump_mode','usable_bonus_percentage','is_adjustable'],'CustomBreakup'=>['fields'=>['id','contest_id','name','start','end','percentage','price']],'Category'=>['fields'=>['id','image','category_name','description','category_color']] ]])
					//->order(['category.sequence'=>'ASC'])
					->order(['category.sequence'=>'ASC'])
					->toArray();
                } else {
                    $result	=	$this->MatchContest->find()
					->where(['MatchContest.is_full'=>0,'SeriesSquad.match_id'=>$decoded['match_id'],'Contest.status'=>ACTIVE,'Contest.trump_mode'=>$trump_mode ])
					->contain(['SeriesSquad'=>['fields'=>['match_id']],'Contest'=>['fields'=>['id','confirmed_winning','contest_size','entry_fee','winning_amount','category_id','multiple_team','max_team_user','trump_mode','usable_bonus_percentage','is_adjustable'],'CustomBreakup'=>['fields'=>['id','contest_id','name','start','end','percentage','price']],'Category'=>['fields'=>['id','image','category_name','description','category_color']] ]])
					//->order(['category.sequence'=>'ASC'])
					->order(['category.sequence'=>'ASC'])
					->toArray();
                }
				
				
			
				// Get contest wise counting
				$query = $this->PlayerTeamContests->find('list', ['keyField'=>'contest_id','valueField'=>'count']);
				$query->select(['count' => $query->func()->count('id'), 'contest_id']);
				$query->where([ 'match_id'=>$decoded['match_id'] ]);
				$teamsJoinedContestWise	=	$query->group(['contest_id'])->toArray();
				
				// Get user joined team id
				$isJoined		=	$this->PlayerTeamContests->find()->select(['PlayerTeamContests.player_team_id','PlayerTeamContests.contest_id'])->where(['match_id'=>$decoded['match_id'],'user_id'=>$decoded['user_id']])->toArray();
				

				$myContestTeamIds	=	[];
				if(!empty($isJoined)){
					foreach($isJoined AS $j){
						$myContestTeamIds[$j->contest_id][] = $j->player_team_id;
					}
				}
				//pr($myContestTeamIds);die;

				$counter	=	0;	
				$categoryArray = [];	
				$contest1 = [];
				if(!empty($result)) {
					
					foreach($result as $contestKey => $contestValue) {

						
						$categoryInfo = $contestValue->category;
						$categoryId = $categoryInfo['id'];

						/* if(!isset($categoryArray[$categoryId])){
							$contest =	[];
							$categoryArray[$categoryId] = $counter;
							$filePath	=	WWW_ROOT.'uploads/category_image/';
							$categoryImage 	=	'';
							if(!empty($categoryInfo['image']) && file_exists($filePath.$categoryInfo['image'])) {
								$categoryImage	=	SITE_URL.'uploads/category_image/'.$categoryInfo['image'];
							}
							$data[$counter]['id']				=	(!empty($categoryInfo['id'])) ? $categoryInfo['id'] : '';
							$data[$counter]['category_title']	=	(!empty($categoryInfo['category_name'])) ? $categoryInfo['category_name'] : '';
							$data[$counter]['category_desc']	=	(!empty($categoryInfo['description'])) ? $categoryInfo['description'] : '';
							$data[$counter]['category_image']	=	$categoryImage;
							$data[$counter]['category_color']	=	(!empty($categoryInfo['category_color'])) ? $categoryInfo['category_color'] : '#18D0F5';
							$counter++;
						} */
						
						$contestInfo = $contestValue->contest;
						
						$customBreakup	=	end($contestInfo->custom_breakup);
						if(!empty($customBreakup) &&!empty($customBreakup->end)) {
							$toalWinner	=	$customBreakup->end;
						} else {
							$toalWinner	=	!empty($customBreakup) ? $customBreakup->start : 0;
						}

						// find team that other users joined
						$teamsJoined	=	(!empty($teamsJoinedContestWise[$contestInfo->id])) ? $teamsJoinedContestWise[$contestInfo->id] : 0;
						

						$myTeamIds	=	[];
						if(!empty($myContestTeamIds)){
							$myTeamIds	=	( !empty($myContestTeamIds[$contestInfo->id]) ) ? $myContestTeamIds[$contestInfo->id] : [];
						}
						
						$customPrice	=	[];
						$first_prize = '';
						if(!empty($contestInfo->custom_breakup)) {
							foreach($contestInfo->custom_breakup as $key=> $customBreakup) {
								if($customBreakup->start == $customBreakup->end) {
									$customPrice[$key]['rank']	=	'Rank '.$customBreakup->start;
								} else {
									$customPrice[$key]['rank']	=	$customBreakup->name;
								}
								// $customPrice[$key]['rank']	=	$customBreakup->name;
								$customPrice[$key]['price']	=	$customBreakup->price;
								if( $first_prize == '' ){
                                    $first_prize = $customBreakup->price;
                                }
							}
						}

						$customPricemain	=	[];
						$winning_amount_maximum = 0;
						if ( $contestValue->contest->is_adjustable ) {
							$custom_breakupmain = $this->CustomBreakupmain->find()
							->where(['contest_id'=>$contestValue->contest->id, 'match_id'=>$decoded['match_id']])
							->toArray();
							if(!empty($custom_breakupmain)) {
								foreach($custom_breakupmain as $key=> $customBreakup) {
									if($customBreakup->start == $customBreakup->end) {
										$customPricemain[$key]['rank']	=	'Rank '.$customBreakup->start;
									} else {
										$customPricemain[$key]['rank']	=	$customBreakup->name;
									}
									$customPricemain[$key]['price']	=	$customBreakup->price;
									
									//Calculate Prize Pool
									$levelWinner = ( $customBreakup->end - ($customBreakup->start-1) );
									$levelPrize = ($levelWinner * $customBreakup->price);
									$winning_amount_maximum += $levelPrize;
								}
							}
						}

						$max_team_user = $contestInfo->max_team_user;
						$winComfimed	=	'no';
						if($contestInfo->confirmed_winning=='' || $contestInfo->confirmed_winning=='0'){
							$winComfimed = 'no';
						} else {
							$winComfimed = $contestInfo->confirmed_winning;
						}

						if($teamsJoined < $contestInfo->contest_size) {

							$dynamic_contest_message = '';
							if( $contestValue->contest->is_adjustable ){
								$dynamic_contest_message = DYNAMIC_CONTEST_MESSAGE;
							}

							$contest[$contestKey]['confirm_winning']=	$winComfimed;
							$contest[$contestKey]['entry_fee']		=	$contestInfo->entry_fee;
							$contest[$contestKey]['prize_money']	=	$contestInfo->winning_amount;
							$contest[$contestKey]['total_teams']	=	$contestInfo->contest_size;
							$contest[$contestKey]['category_id']	=	(!empty($contestInfo->category_id)) ? $contestInfo->category_id : '';
							$contest[$contestKey]['contest_id']		=	$contestInfo->id;
							$contest[$contestKey]['total_winners']	=	(int) $toalWinner;
							$contest[$contestKey]['teams_joined']	=	$teamsJoined;
							$contest[$contestKey]['is_joined']		=	!empty($myTeamIds) ? true : false;
							$contest[$contestKey]['multiple_team']	=	($contestInfo->multiple_team == 'yes') ? true : false;
							$contest[$contestKey]['max_team_user']  = 	$max_team_user;
							$contest[$contestKey]['usable_bonus_percentage']=	$contestInfo->usable_bonus_percentage;
							$contest[$contestKey]['invite_code']	=	$contestValue->invite_code;
							$contest[$contestKey]['breakup_detail']	=	$customPrice;
							$contest[$contestKey]['my_team_ids']	=	$myTeamIds;
							$contest[$contestKey]['trump_mode']		=	$contestInfo->trump_mode;
							$contest[$contestKey]['winning_amount_maximum']   = (string)$winning_amount_maximum;
							$contest[$contestKey]['dynamic_contest_message']  = $dynamic_contest_message;
							$contest[$contestKey]['is_adjustable']	    = $contestValue->contest->is_adjustable;
							$contest[$contestKey]['breakup_detail_maximum']	=	$customPricemain;
							$contest[$contestKey]['first_prize']        = (int)$first_prize;

							
						} else {
							$MatchContestTableQuery = $MatchContestTable->query();
							$creturn = $MatchContestTableQuery->update()
								->set(['is_full' => 1])
								->where([ 'match_id' => $contestValue->match_id,'contest_id' => $contestValue->contest_id ])
								->execute();
						}

						
						
					}

					$contest1	=	array_values($contest);
					
				}


				$myTeams	=	$this->PlayerTeams->find()
								->where(['user_id'=>$decoded['user_id'],'match_id'=>$decoded['match_id']])->count();

				$my_nteams	=	$this->PlayerTeams->find()
				->where([ 'user_id'=>$decoded['user_id'], 'match_id'=>$decoded['match_id'], 'trump_mode'=>0 ])->count();

				$my_tteams	=	$this->PlayerTeams->find()
				->where([ 'user_id'=>$decoded['user_id'], 'match_id'=>$decoded['match_id'], 'trump_mode'=>1 ])->count();
								
				$myContest	=	$this->PlayerTeamContests->find()
								->where(['user_id'=>$decoded['user_id'],'match_id'=>$decoded['match_id']])->group('contest_id')->count();
								
				$data = array_values($data); // Added on 20-05-2019 due to json key added autometically

				//$data1->match_contest	=	$data;
				$data1->match_all_contest = $contest1;
				$data1->my_teams		=	$myTeams;
				$data1->my_nteams		=	$my_nteams;
				$data1->my_tteams		=	$my_tteams;
				$data1->my_contests		=	$myContest;
				
				$status	=	true;
			} else {
				$message	=	__("Match id is empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'tokenexpire'=>0,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function playerList() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$guru_url  	=	''; 
		$this->loadModel('SeriesSquad');
		$this->loadModel('SeriesPlayers');
		$this->loadModel('LiveScore');
		$this->loadModel('PlayerTeams');
		$this->loadModel('PlayerTeamDetails');
		$is_lineup_declare = 0;
		if(!empty($decoded)) {
			if(!empty($decoded['match_id']) && !empty($decoded['series_id']) && !empty($decoded['local_team_id']) && !empty($decoded['visitor_team_id'])) {
				

				$result	=	$this->SeriesSquad->find()
				->select(['series_id','type','localteam_players','visitorteam_players'])
				->where(['SeriesSquad.match_id'=>$decoded['match_id']])
				->first();

				if(!empty($result)) {

					$json_players_arry = [];
					$player_ids_array = [];
					if(!empty($result->localteam_players)){
						$localteam_players = json_decode($result->localteam_players,true);
						$json_players_arry[$localteam_players['team_id']] = $localteam_players['players'];
						if(!empty($localteam_players['players'])){
							foreach($localteam_players['players'] AS $key => $val){
								$player_ids_array[] = $val['player_id'];
							}
						}
					}
					if(!empty($result->visitorteam_players)){
						$visitorteam_players = json_decode($result->visitorteam_players,true);
						$json_players_arry[$visitorteam_players['team_id']] = $visitorteam_players['players'];
						if(!empty($visitorteam_players['players'])){
							foreach($visitorteam_players['players'] AS $key => $val){
								$player_ids_array[] = $val['player_id'];
							}
						}
					}
					//pr($player_ids_array);die;

					$type			=	strtolower($result->type);
					if(!empty($player_ids_array)){
						$seriesPlayers	=	$this->SeriesPlayers->find()
						->select(['SeriesPlayers.id','SeriesPlayers.series_id','SeriesPlayers.series_name','SeriesPlayers.team_id','SeriesPlayers.team_name','SeriesPlayers.player_id','SeriesPlayers.player_name','SeriesPlayers.player_role','PlayerRecord.id','PlayerRecord.player_id','PlayerRecord.player_name','PlayerRecord.image','PlayerRecord.playing_role','PlayerRecord.teams','PlayerRecord.player_credit'])
						->where([$type=>'True','series_id'=>$result->series_id,'SeriesPlayers.team_id IN'=>[$decoded['local_team_id'],$decoded['visitor_team_id']], 'SeriesPlayers.player_id IN'=>$player_ids_array ])
						->contain(['PlayerRecord'])
						->group(['PlayerRecord.player_id'])
						->toarray();
					} else {
						$seriesPlayers	=	$this->SeriesPlayers->find()
						->select(['SeriesPlayers.id','SeriesPlayers.series_id','SeriesPlayers.series_name','SeriesPlayers.team_id','SeriesPlayers.team_name','SeriesPlayers.player_id','SeriesPlayers.player_name','SeriesPlayers.player_role','PlayerRecord.id','PlayerRecord.player_id','PlayerRecord.player_name','PlayerRecord.image','PlayerRecord.playing_role','PlayerRecord.teams','PlayerRecord.player_credit'])
						->where([$type=>'True','series_id'=>$result->series_id,'SeriesPlayers.team_id IN'=>[$decoded['local_team_id'],$decoded['visitor_team_id']] ])
						->contain(['PlayerRecord'])
						->group(['PlayerRecord.player_id'])
						->toarray();
					}
					
					
					//New Logic for lineup
					
					$defaultVal = 2;
					$playerIsLineup	=	$this->LiveScore->find('list', ['keyField'=>'playerId','valueField'=>'is_lineup'])->where(['seriesId'=>$decoded['series_id'], 'matchId' => $decoded['match_id']])->toArray();
					foreach($playerIsLineup AS $key => $value){
						if($value==1){
							$is_lineup_declare = 1;
							$defaultVal = 0;
							break;
						}
					}
					//New Logic for lineup

					//Lineup work logic
					if($is_lineup_declare){
						$SeriesSquadTable = TableRegistry::get('SeriesSquad');
						$SeriesSquadQuery = $SeriesSquadTable->query();
						$SeriesSquadQuery->update()
							->set(['is_lineup' => 1])
							->where(['series_id' => $decoded['series_id'], 'match_id'=>$decoded['match_id']])
							->execute();
					}
					//Lineup work logic

					//Get Player points
					$query = $this->LiveScore->find('list', ['keyField'=>'playerId','valueField'=>'totalpoint']);
					$query->select(['totalpoint' => $query->func()->sum('point'), 'playerId']);
					$query->where([ 'seriesId'=>$decoded['series_id'] ]);
					$playerPoints	=	$query->group(['playerId'])->toArray();

					//Get Player selected count
					$query = $this->PlayerTeamDetails->find('list', ['keyField'=>'player_id','valueField'=>'selectedby']);
					$query->select(['selectedby' => $query->func()->count('player_id'), 'player_id']);
					$query->where([ 'match_id'=>$decoded['match_id'] ]);
					$selectedbycount	=	$query->group(['player_id'])->toArray();

					//Get Player selected count captain
					$query = $this->PlayerTeamDetails->find('list', ['keyField'=>'player_id','valueField'=>'selectedby']);
					$query->select(['selectedby' => $query->func()->count('player_id'), 'player_id']);
					$query->where([ 'match_id'=>$decoded['match_id'], 'is_corvc' => 1 ]);
					$c_selectedbycount	=	$query->group(['player_id'])->toArray();

					//Get Player selected count vice captain
					$query = $this->PlayerTeamDetails->find('list', ['keyField'=>'player_id','valueField'=>'selectedby']);
					$query->select(['selectedby' => $query->func()->count('player_id'), 'player_id']);
					$query->where([ 'match_id'=>$decoded['match_id'], 'is_corvc' => 2 ]);
					$vc_selectedbycount	=	$query->group(['player_id'])->toArray();

					// Team count
					$team_count	=	$this->PlayerTeams->find()->select(['id'])->where(['series_id'=>$decoded['series_id'],'match_id'=>$decoded['match_id']])->count();

					
					foreach($seriesPlayers as $players) {
						
						$players->player_points		=	(!empty($playerPoints[$players->player_id])) ? $playerPoints[$players->player_id] : 0;

						$selectedBy	=	$selectedByC	=	$selectedByVc	=	0;
						$teamPlayer	=	(!empty($selectedbycount[$players->player_id])) ? $selectedbycount[$players->player_id] : 0;
						if(!empty($teamPlayer)) {
							$selectedBy	=	($teamPlayer/$team_count) * 100;
						}
						$players->selected_by		=	number_format($selectedBy,0).'%';

						$teamPlayer	=	(!empty($c_selectedbycount[$players->player_id])) ? $c_selectedbycount[$players->player_id] : 0;
						if(!empty($teamPlayer)) {
							$selectedByC	=	($teamPlayer/$team_count) * 100;
						}
						$players->selected_by_ac		=	number_format($selectedByC,0).'%';

						$teamPlayer	=	(!empty($vc_selectedbycount[$players->player_id])) ? $vc_selectedbycount[$players->player_id] : 0;
						if(!empty($teamPlayer)) {
							$selectedByVc	=	($teamPlayer/$team_count) * 100;
						}
						$players->selected_by_avc		=	number_format($selectedByVc,0).'%';

						$players->player_credit		=	0;
						$players->double_player_credit = 0;
						if(!empty($players->player_record->player_credit)){
							$players->player_credit		=	$players->player_record->player_credit;
							$players->double_player_credit=(double)$players->player_record->player_credit;
						}

						$json_player_info = ( isset( $json_players_arry[$players->team_id][$players->player_id] )) ? $json_players_arry[$players->team_id][$players->player_id] : [];

						if(!empty($json_player_info)){
							$players->player_role = $json_player_info['player_role'];
							$players->player_record->playing_role	=	$json_player_info['player_role'];
							$players->player_record->player_credit	=	(string)$json_player_info['player_credit'];

							$players->player_credit		=	(string)$json_player_info['player_credit'];
							$players->double_player_credit=(double)$json_player_info['player_credit'];
						}

						$players->is_lineup		=	(isset($playerIsLineup[$players->player_id])) ? $playerIsLineup[$players->player_id] : $defaultVal; // 0/1

						if(!empty($players->player_record) && file_exists(WWW_ROOT.'/uploads/player_image/'.$players->player_record->image)) {
							$players->player_record->image	=	SITE_URL.'uploads/player_image/'.$players->player_record->image;
						}
					}
					$guru_url = $result->guru_url;
				} else {
					$message	=	__('Series does not exist.',true);
				}
				$data1	=	$seriesPlayers;
				$status	=	true;
			} else {
				$message	=	__("match id, series id, local team id or visitor team id are empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		//$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		$response_data	=	array('status'=>$status,'message'=>$message,'is_lineup_declare'=>$is_lineup_declare,'data'=>$data1,'guru_url'=>$guru_url);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function createTeam() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('PlayerTeams');
		$this->loadModel('PlayerTeamDetails');
		$this->loadModel('PlayerTeamContests');
		$this->loadModel('SeriesSquad');
		if(!empty($decoded)) {
			if(!empty($decoded['user_id']) && !empty($decoded['match_id']) && !empty($decoded['series_id']) && !empty($decoded['player_id'])) {

				$trump_mode = ( isset($decoded['trump_mode']) ) ? $decoded['trump_mode'] : 0;
				$decoded_player_id = $decoded['player_id'];
				//$decoded_player_id	=	array_unique($decoded_player_id);
				if($trump_mode){
					$checkCount = 12;
				} else {
					$checkCount = 11;
				}
				if(count($decoded_player_id) != $checkCount) {
					$message	=	__('Please select valid team ',true);
					$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
					echo json_encode(array('response' => $response_data));
					die;
				}

				if($trump_mode){
					if (($key = array_search($decoded['twelveth'], $decoded_player_id)) !== false) {
						unset($decoded_player_id[$key]);
					}
				}

				$seriesSquad	=	$this->SeriesSquad->find()->where(['series_id'=>$decoded['series_id'],'match_id'=>$decoded['match_id']])->first();
				if(!empty($seriesSquad)){
					$match_date = $seriesSquad->date;
					$match_time = $seriesSquad->time;
					$match_timestamp = strtotime("$match_date $match_time")+300; // 5 Min
					$curent_timestamp = time();
					if($match_timestamp < $curent_timestamp){
						$message	=	__('Match started, you can not add or edit team now.',true);
						$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
						echo json_encode(array('response' => $response_data));
						die;
					}
				}

				$teamDataa	=	$this->PlayerTeams->find()
									->where(['user_id'=>$decoded['user_id'],'match_id'=>$decoded['match_id'],'series_id'=>$decoded['series_id'],'captain'=>$decoded['captain'],'vice_captain'=>$decoded['vice_captain']])
									->contain(['PlayerTeamDetails'])->toArray();
				// pr($teamDataa);die;
				$statusAdd	=	false;
				if(!empty($teamDataa)) {
					$checkPlayer	=	[];
					if(!empty($decoded['team_id'])) {
						foreach($teamDataa as $playerKey => $playerTeams) {
							$isPlayer	=	[];
							if(count($teamDataa) > 1) {
								if($decoded['team_id'] != $playerTeams->id) {
									// pr($playerTeams);
									if(!empty($playerTeams->player_team_details)) {
										foreach($playerTeams->player_team_details as $playerDetail) {
											if(in_array($playerDetail->player_id,$decoded_player_id)) {
												$isPlayer[]	=	1;
											} else {
												$isPlayer[]	=	0;
											}
										}
									}
									if(in_array(0,$isPlayer) && $playerTeams->captain == $decoded['captain'] && $playerTeams->vice_captain == $decoded['vice_captain']) {
										$checkPlayer[$playerKey]	=	1;
									} else {
										$checkPlayer[$playerKey]	=	0;
									}
								}
							} else {
								$checkPlayer[0]	=	1;
							}
						}
					} else {
						foreach($teamDataa as $playerKey => $playerTeams) {
							$isPlayer	=	[];
							if(!empty($playerTeams->player_team_details)) {
								foreach($playerTeams->player_team_details as $playerDetail) {
									if(in_array($playerDetail->player_id,$decoded_player_id)) {
										$isPlayer[]	=	1;
									} else {
										$isPlayer[]	=	0;
									}
								}
							}
							if(in_array(0,$isPlayer)) {
								$checkPlayer[$playerKey]	=	1;
							} else {
								$checkPlayer[$playerKey]	=	0;
							}
						}
					}
					
					if(in_array(1,$checkPlayer)) {
						$statusAdd	=	true;
					}
				} else {
					$statusAdd	=	true;
				}
				// pr($statusAdd);
				// die;
				if($statusAdd == true) {
					$playerData	=	[];
					if(!empty($decoded_player_id)) {
						foreach($decoded_player_id as $key=> $playerId) {
							$playerData[$key]['player_id']	=	$playerId;
							$playerData[$key]['match_id']	=	$decoded['match_id'];

							$is_corvc = 0;
							if ($playerId == $decoded['captain']) {
								$is_corvc = 1;
							} else if ($playerId == $decoded['vice_captain']){
								$is_corvc = 2;
							}
							$playerData[$key]['is_corvc']	=	$is_corvc;

							$playerData[$key]['created']	=	date('Y-m-d H:i:s');
						}
					}
					
					if(!empty($decoded['team_id'])) {
						$team	=	$this->PlayerTeams->find()->where(['id'=>$decoded['team_id']])->contain(['PlayerTeamDetails'])->first();
						if(!empty($team->player_team_details)) {
							foreach($team->player_team_details as $playerTeamDetail) {
								$this->PlayerTeamDetails->delete($playerTeamDetail);
							}
						}
					} else {
						if($trump_mode){
							$getTeamCount	=	$this->PlayerTeams->find()
							->where(['user_id'=>$decoded['user_id'],'match_id'=>$decoded['match_id'],'series_id'=>$decoded['series_id'],'trump_mode'=>1 ])
							->group(['team_count'])
							->count();
						} else {
							$getTeamCount	=	$this->PlayerTeams->find()
							->where(['user_id'=>$decoded['user_id'],'match_id'=>$decoded['match_id'],'series_id'=>$decoded['series_id'],'trump_mode'=>0 ])
							->group(['team_count'])
							->count();
						}
						
						$teamcount		=	$getTeamCount + 1;
						$team	=	$this->PlayerTeams->newEntity();
						$decoded['team_count']		=	$teamcount;
						$decoded['created']		=	date('Y-m-d H:i:s');
					}
					
					$decoded['player_team_details']	=	$playerData;
					// pr($decoded);
					$this->PlayerTeams->patchEntity($team,$decoded);
					if($teamData = $this->PlayerTeams->save($team)) {
						$status	=	true;
						$data1->team_id	=	$teamData->id;
						if(!empty($decoded['team_id'])) {
							$message	=	__("Team has been updated successfully.", true);
						} else {
							$message	=	__("Team has been created successfully.", true);
						}
					} else {
						if(!empty($decoded['team_id'])) {
							$message	=	__("Team could not update.", true);
						} else {
							$message	=	__("Team could not create.", true);
						}
					}
				} else {
					$message	=	__("This team is already created, please make changes in team players or change team captain/vice captain to continue.", true);
				}
			} else {
				$message	=	__("match id, series id or Player list are empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}


		$myTeamsCount	=	$this->PlayerTeams->find()
			->where(['user_id'=>$decoded['user_id'],'match_id'=>$decoded['match_id'],'series_id'=>$decoded['series_id']])->count();


		$joinedContestCount	=	$this->PlayerTeamContests->find()
			->where(['match_id'=>$decoded['match_id'],'series_id'=>$decoded['series_id'],'user_id'=>$decoded['user_id']])
			->count();

		$my_nteams	=	$this->PlayerTeams->find()
		->where([ 'user_id'=>$decoded['user_id'], 'match_id'=>$decoded['match_id'], 'trump_mode'=>0 ])->count();

		$my_tteams	=	$this->PlayerTeams->find()
		->where([ 'user_id'=>$decoded['user_id'], 'match_id'=>$decoded['match_id'], 'trump_mode'=>1 ])->count();	


		//$data1->my_team_count		=	$myTeamsCount;
		//$data1->my_contest_count	=	$joinedContestCount;

		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1,'my_team_count'=>$myTeamsCount,'my_contest_count'=>$joinedContestCount,'my_nteams'=>$my_nteams,'my_tteams'=>$my_tteams);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function joinContest() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Users');
		$this->loadModel('Contest');
		$this->loadModel('SeriesSquad');
		$this->loadModel('PlayerTeams');
		$this->loadModel('UserContestRewards');
		$this->loadModel('PlayerTeamContests');
		$this->loadModel('JoinContestDetails');
		if(!empty($decoded)) {
			if(!empty($decoded['user_id']) && !empty($decoded['match_id']) && !empty($decoded['series_id']) && !empty($decoded['contest_id'])) {
				$authUser	=	$this->Users->find()->where(['id'=>$decoded['user_id'],'status'=>ACTIVE])->select(['id'])->first();
				if(!empty($authUser)) {
					
					$currentDate=	date('Y-m-d');
					$liveTime	=	date('H:i',strtotime('+30 min'));
					$currentTime=	date('H:i');
					$liveMatch	=	$this->SeriesSquad->find()
									->where(['date' => $currentDate,'OR'=>[['time >='=>$currentTime,'time <='=>$liveTime],'match_status'=>MATCH_INPROGRESS],'SeriesSquad.status'=>ACTIVE,'match_id'=>$decoded['match_id'],'series_id'=>$decoded['contest_id']])
									// ->select([''])
									->first();

					$isRightMatch	=	$this->SeriesSquad->find()
									->where([ 'match_status != '=> MATCH_CANCELLED,  'match_id'=>$decoded['match_id'],'series_id'=>$decoded['series_id']])
									->count();

					if( empty($liveMatch) && $isRightMatch ) {
						$contestData=	$this->Contest->find()
												->select(['entry_fee','contest_size','contest_type','auto_create','admin_comission','usable_bonus_percentage','multiple_team','max_team_user','trump_mode'])
												->where(['id'=>$decoded['contest_id']])->first();

						$teamId	=	'';
						if(empty($decoded['team_id'])) {

							if( $contestData->trump_mode ){

								$playerTeam	=	$this->PlayerTeams->find()
								->select(['PlayerTeams.id'])
								->where(['user_id'=>$decoded['user_id'],'match_id'=>$decoded['match_id'],'series_id'=>$decoded['series_id'],'trump_mode'=>1 ])
								->first();

							} else {
								$playerTeam	=	$this->PlayerTeams->find()
								->select(['PlayerTeams.id'])
								->where(['user_id'=>$decoded['user_id'],'match_id'=>$decoded['match_id'],'series_id'=>$decoded['series_id'],'trump_mode'=> 0 ])
								->first();
							}
							$teamId	=	$playerTeam->id;
						} else {
							$teamId	=	$decoded['team_id'];
						}


						if(empty($teamId)){
							$response_data	=	array('status'=>false, 'message'=>"You don't have any team yet, please create team first.", 'data'=>$data1);
							echo json_encode(array('response' => $response_data));
							die;
						}


						$result	=	$this->PlayerTeamContests->find()->where(['user_id'=>$decoded['user_id'],'match_id'=>$decoded['match_id'],'series_id'=>$decoded['series_id'],'contest_id'=>$decoded['contest_id'],'player_team_id'=>$teamId])->select(['PlayerTeamContests.id'])->first();
						
						$sameContest	=	$this->PlayerTeamContests->find()->where(['contest_id'=>$decoded['contest_id'],'user_id'=>$decoded['user_id'],'match_id'=>$decoded['match_id'],'series_id'=>$decoded['series_id']])->select(['PlayerTeamContests.id'])->count();


						$maxTeamSize	=	11;
						if( $contestData->multiple_team !='yes' ){
                            $maxTeamSize = 1;
                        } else {
                            if(!empty($contestData->max_team_user)){
                                $maxTeamSize = $contestData->max_team_user;
                            } else {
                                $maxTeamSize = 1;
                            }
                            
                        }


						if($sameContest < $maxTeamSize) {
							if(empty($result)) {
								

								if( $contestData->multiple_team !='yes' && $sameContest > 0 ){
									$message	=	__("You are already joined with one team.", true);
								} else {
									// get contests count of perticular match and series
									$joinedContest	=	$this->PlayerTeamContests->find()->where(['match_id'=>$decoded['match_id'],'series_id'=>$decoded['series_id'],'contest_id'=>$decoded['contest_id']])->count();
									$joinStatus	=	false;
									if(!empty($joinedContest)) {
										if($joinedContest < $contestData->contest_size) {
											$joinStatus	=	true;
										}
									} else {
										$joinStatus	=	true;
									}
									
									if($joinStatus == true) {


										$user	=	$this->Users->find()
														->select(['id','cash_balance','winning_balance','bonus_amount','ReferalCodeDetails.user_id','ReferalCodeDetails.refered_by','ReferalCodeDetails.refered_by_amount','ReferalCodeDetails.id'])
														->where(['Users.id'=>$decoded['user_id'],'Users.status'=>ACTIVE])
														->contain(['ReferalCodeDetails'])->first();


										if( !empty($user) && !empty($contestData) ) {

											$usable_bonus_percentage=	$contestData->usable_bonus_percentage;				
											$entryFee	=	!empty($contestData) ? $contestData->entry_fee : 0;

											$adminPer	=	$usable_bonus_percentage;
											$useAmount	=	($adminPer /100) * $entryFee;
											$bonusAmount=	0;
											// Added on 21-05-2019
											if(!empty($user->bonus_amount) && $user->bonus_amount > 0) {
												if($useAmount <= $user->bonus_amount) {
													$bonusAmount	=	$useAmount;
												} else {
													$bonusAmount	=	$user->bonus_amount;
												}
											}


											$userAB = $user->cash_balance + $user->winning_balance + $bonusAmount;
											if($userAB < $entryFee){
												$response_data	=	array('status'=>false, 'message'=>"You don't have sufficient amount in your wallet to join this contest.", 'data'=>$data1);
												echo json_encode(array('response' => $response_data));
												die;
											}

										} else {
											$response_data	=	array('status'=>false, 'message'=>"There is some error, please try later.", 'data'=>$data1);
											echo json_encode(array('response' => $response_data));
											die;
										}

										$contest	=	$this->PlayerTeamContests->newEntity();
										$this->PlayerTeamContests->patchEntity($contest,$decoded);
										$contest->player_team_id	=	$teamId;
										if($this->PlayerTeamContests->save($contest)) {
											
											$noOfContest	=	$this->PlayerTeamContests->find()->where(['match_id'=>$decoded['match_id'],'series_id'=>$decoded['series_id'],'contest_id'=>$decoded['contest_id']])->count();
											// AutoCreate Contest
											if($contestData->auto_create == 'yes'){
												if($noOfContest == $contestData->contest_size){
													$this->createAutoContest($decoded['contest_id'],$decoded['series_id'],$decoded['match_id']);
												}
											}

											
													
											if(!empty($contestData)) {
												$contestType=	$contestData->contest_type;
												$usable_bonus_percentage=	$contestData->usable_bonus_percentage;
												$admin_comission =	$contestData->admin_comission;
												$entryFee	=	!empty($contestData) ? $contestData->entry_fee : 0;
												if($contestType == 'Paid') {

													

													// create transation log for joining Contest
													$joinContestTxnId	=	'JL'.date('Ymd').time().$decoded['user_id'];
													$this->saveTransaction($decoded['user_id'],$joinContestTxnId,JOIN_CONTEST,$entryFee);
													
													
													/* $adminPer	=	Configure::read('Admin.setting.admin_percentage');
													if( $usable_bonus_percentage >0 ){
														$adminPer	=	$usable_bonus_percentage;
													} */

													$adminPer	=	$usable_bonus_percentage;
													$useAmount	=	($adminPer /100) * $entryFee;

													$saveData	=	[];
													if(!empty($user)) {
														$cashAmount	=	0;
														$winAmount	=	0;
														$bonusAmount=	0;

														// Added on 21-05-2019
														if(!empty($user->bonus_amount) && $user->bonus_amount > 0) {
															if($useAmount <= $user->bonus_amount) {
																$remainingFee	=	$entryFee - $useAmount;
																$saveData['bonus_amount']	=	$user->bonus_amount - $useAmount;
																$bonusAmount	=	$useAmount;
																// $this->saveJoinContestDetail($decoded,$useAmount,CASH_BONUS);
															} else {
																$remainingFee	=	$entryFee - $user->bonus_amount;
																$saveData['bonus_amount']	=	0;
																$bonusAmount	=	$user->bonus_amount;
															}
														} else {
															$saveData['bonus_amount']	=	0;
															$remainingFee	=	$entryFee;
														}

														
														if(!empty($remainingFee)) {
															$cashBalance=	$user->cash_balance;
															if(!empty($cashBalance)) {
																$cashBal		=	($cashBalance > $remainingFee) ? $cashBalance - $remainingFee : 0;
																$cashAmount		=	($cashBalance > $remainingFee) ? $remainingFee : $cashBalance;
																$remainingFee	=	($cashBalance < $remainingFee) ? $remainingFee - $cashBalance : 0;
																$saveData['cash_balance']	=	$cashBal;
															}
														}

														if(!empty($remainingFee)) {
															$winningBal	=	$user->winning_balance;
															if(!empty($winningBal)) {
																$winningBal1	=	($winningBal > $remainingFee) ? $winningBal - $remainingFee : 0;
																$winAmount	=	($winningBal > $remainingFee) ? $remainingFee : $winningBal;
																$remainingFee	=	($winningBal < $remainingFee) ? $remainingFee - $winningBal : 0;
																$saveData['winning_balance']	=	$winningBal1;
															}
														}

														$this->saveJoinContestDetail($decoded,$bonusAmount,$winAmount,$cashAmount);
														
														
													}
													
													// add reward if 20 contest are completed Start
													$usersContest	=	$this->PlayerTeamContests->find()->where(['user_id'=>$decoded['user_id'],'Contest.contest_type LIKE'=>'Paid'])->group(['PlayerTeamContests.series_id','PlayerTeamContests.match_id','PlayerTeamContests.contest_id'])->contain(['Contest'])->count();
													
													if($usersContest % 20 == 0) {
														$userRewards	=	$this->UserContestRewards->newEntity();
														$userRewards->user_id	=	$decoded['user_id'];
														$userRewards->reward	=	20;
														$userRewards->dete		=	date('Y-m-d');
														
														if($this->UserContestRewards->save($userRewards)) {
															// $user->bonus_amount	=	$user->bonus_amount + 20;
															$saveData['bonus_amount']	=	$saveData['bonus_amount'] + 20;
															$txnId	=	'CB'.date('Ymd').time().$decoded['user_id'];
															$this->saveTransaction($decoded['user_id'],$txnId,LEVEL_UP,20);
														}
													}
													// add reward if 20 contest are completed End
													
													if(!empty($saveData)) {
														$this->Users->patchEntity($user,$saveData);
														$this->Users->save($user);
													}
													

												}
											}
											$status	=	true;
											$message	=	__("Contest Joined successfully.", true);
										}
									} else {
										$message	=	__("This contest is full, please join other contest.", true);
									}
								}
								
								
							} else {
								$message	=	__("Already Joined Contest.", true);
							}
						} else {
							$message	=	__("You can not add more than ".$maxTeamSize." teams.", true);
						}
					} else {
						$message	=	__('You can not join contest',true);
					}
				} else {
					$message	=	__('Invalid user id.',true);
				}
			} else {
				$message	=	__("user id, match id, series id or contest id are empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}

		$myTeamsCount	=	$this->PlayerTeams->find()
			->where(['user_id'=>$decoded['user_id'],'match_id'=>$decoded['match_id'],'series_id'=>$decoded['series_id']])->count();


		$joinedContestCount	=	$this->PlayerTeamContests->find()
			->where(['match_id'=>$decoded['match_id'],'series_id'=>$decoded['series_id'],'user_id'=>$decoded['user_id']])
			->count();

		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1,'my_team_count'=>$myTeamsCount,'my_contest_count'=>$joinedContestCount);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function joinContestWalletAmount() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Users');
		$this->loadModel('Contest');
		if(!empty($decoded)) {
			$setting	=	$this->settingData();
			if(!empty($decoded['user_id'])) {
				$userdata	=	$this->Users->find()
									->select(['cash_balance','winning_balance','bonus_amount'])
									->where(['id'=>$decoded['user_id'],'Users.status'=>ACTIVE])->first();
					if(!empty($userdata)) {
							$usable_bonus_percentage = 0;
							if(!empty($decoded['contest_id'])) {
								$contestData=	$this->Contest->find()->where(['id'=>$decoded['contest_id']])->first();
								$entryFee	=	!empty($contestData) ? $contestData->entry_fee : 0;
								$usable_bonus_percentage	=	!empty($contestData) ? $contestData->usable_bonus_percentage : 0;
							} else {
								$entryFee	=	$decoded['entry_fee'];
							}

							/* $adminPer	=	!empty($setting->admin_percentage) ? $setting->admin_percentage : 0;
							if( $usable_bonus_percentage >0 ){
								$adminPer	=	$usable_bonus_percentage;
							} */

							$adminPer	=	$usable_bonus_percentage;

							// Commented on 21-05-2019
							/* $useAmount	=	($entryFee > 50) ? 50 : $entryFee;
							$useAmount	=	50;
							$usableAmt	=	$cashBalance	=	$winningBalance	=	0;
							
							$this->loadModel('JoinContestDetails');
							$joinContestDetails	=	$this->JoinContestDetails->find()->where(['series_id'=>$decoded['series_id'],'match_id'=>$decoded['match_id'],'user_id'=>$decoded['user_id']])->sumOf('bonus_amount');
							if($joinContestDetails <= 50) {
								$useAmount	=	50 - $joinContestDetails;
							}
							$useAmount	=	($entryFee > $useAmount) ? $useAmount : $entryFee;
							if(!empty($userdata)) {
								if($useAmount > 0 && $joinContestDetails < 50) {
									if($useAmount > $userdata->bonus_amount) {
										$usableAmt	=	$userdata->bonus_amount;
									} else {
										$usableAmt	=	$useAmount;
									}
								}
								$cashBalance	=	$userdata->cash_balance;
								$winningBalance	=	$userdata->winning_balance;
							} */

							// Added on 21-05-2019
							$useAmount	=	($adminPer /100) * $entryFee;
							$usableAmt	=	$cashBalance	=	$winningBalance	=	0;
							if(!empty($userdata)) {
								if($useAmount > $userdata->bonus_amount) {
									$usableAmt	=	$userdata->bonus_amount;
								} else {
									$usableAmt	=	$useAmount;
								}
								$cashBalance	=	!empty($userdata->cash_balance)?$userdata->cash_balance:0;
								$winningBalance	=	!empty($userdata->winning_balance)?$userdata->winning_balance:0;
							}
							
							$data['cash_balance']	=	$cashBalance;
							$data['winning_balance']=	$winningBalance;
							$data['usable_bonus']	=	number_format($usableAmt,2);
							$data['entry_fee']		=	$entryFee;
							$data['usable_bonus_percentage'] = $adminPer;
							
							$status	=	true;
							$data1	=	$data;
						
					} else {
						$message	=	__('Invalid user id.',true);
					}
			} 
			else 
			{
				$message	=	__("user id or contest id are empty.", true);
			} 
			
		} 
		else 
		{
			$message	=	__("You are not authenticated user.", true);
		}


		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function applyCouponCode() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Users');
		$this->loadModel('PaymentOffers');
		$this->loadModel('UserCouponCodes');
		if(!empty($decoded)) {
			$authUser	=	$this->Users->find()->where(['id'=>$decoded['user_id'],'status'=>ACTIVE])->first();
			if(!empty($authUser)) {
				$currentDateTime	=	date('Y-m-d H:i:s');
				$couponData	=	$this->PaymentOffers->find()->where(['coupon_code LIKE'=>$decoded['coupon_code'],'expiry_date >='=>$currentDateTime,'status'=>ACTIVE])->first();
				if(!empty($couponData)) {
					$userCouponCount=	$this->UserCouponCodes->find()->where(['user_id'=>$decoded['user_id'],'coupon_code_id'=>$couponData->id,'status'=>ACTIVE])->count();
					if($userCouponCount >= $couponData->per_user_limit && !empty($couponData->per_user_limit)) {
						$message	=	__('You have used your limit.',true);
					} else {
						if(!empty($couponData->usage_limit)) {
							$couponCount	=	$this->UserCouponCodes->find()->where(['coupon_code_id'=>$couponData->id,'status'=>ACTIVE])->count();
							if($couponCount >= $couponData->usage_limit) {
								$message	=	__('Coupon code has expired.',true);
							} else {
								$userCoupon	=	$this->UserCouponCodes->newEntity();
								$saveCouonData['coupon_code_id']=	$couponData->id;
								$saveCouonData['user_id']		=	$decoded['user_id'];
								$saveCouonData['applied_on']	=	date('Y-m-d');
								$saveCouonData['min_amount']	=	!empty($couponData->min_amount) ? (float) $couponData->min_amount : 0;
								$saveCouonData['in_percentage']	=	($couponData->max_cashback_percent > 0) ? true : false;
								$saveCouonData['created']		=	date('Y-m-d H:i:s');
								if($couponData->max_cashback_percent > 0) {
									$saveCouonData['discount_amount']	=	$couponData->max_cashback_percent;
									$saveCouonData['max_discount']		=	$couponData->max_cashback_amount;
								} else {
									$saveCouonData['discount_amount']	=	$couponData->max_cashback_amount;
									$saveCouonData['max_discount']		=	$couponData->max_cashback_amount;
								}
								$this->UserCouponCodes->patchEntity($userCoupon,$saveCouonData);
								// $userCoupon->status	=	ACTIVE;
								if($result = $this->UserCouponCodes->save($userCoupon)) {
									$result->coupon_id	=	$result->coupon_code_id;
									$data1		=	$result;
									$status		=	true;
									$message	=	__('Coupon applied successfully.',true);
								}
							}
						} else {
							$userCoupon	=	$this->UserCouponCodes->newEntity();
							$saveCouonData['coupon_code_id']=	$couponData->id;
							$saveCouonData['user_id']		=	$decoded['user_id'];
							$saveCouonData['applied_on']	=	date('Y-m-d');
							$saveCouonData['min_amount']	=	!empty($couponData->min_amount) ? (float) $couponData->min_amount : 0;
							$saveCouonData['in_percentage']	=	($couponData->max_cashback_percent > 0) ? true : false;
							$saveCouonData['created']		=	date('Y-m-d H:i:s');
							if($couponData->max_cashback_percent > 0) {
								$saveCouonData['discount_amount']	=	$couponData->max_cashback_percent;
								$saveCouonData['max_discount']		=	$couponData->max_cashback_amount;
							} else {
								$saveCouonData['discount_amount']	=	$couponData->max_cashback_amount;
								$saveCouonData['max_discount']		=	$couponData->max_cashback_amount;
							}
							$this->UserCouponCodes->patchEntity($userCoupon,$saveCouonData);
							// $userCoupon->status	=	ACTIVE;
							if($result = $this->UserCouponCodes->save($userCoupon)) {
								$result->coupon_id	=	$result->coupon_code_id;
								$data1		=	$result;
								$status		=	true;
								$message	=	__('Coupon applied successfully.',true);
							}
						}
					}
				} else {
					$message	=	__("Coupon Code is not valid.", true);
				}
			} else {
				$message	=	__('Invalid user id.',true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function switchTeam() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('PlayerTeamContests');
		$this->loadModel('PlayerTeams');
		$this->loadModel('Users');
		if(!empty($decoded)) {
			if(!empty($decoded['user_id']) && !empty($decoded['match_id']) && !empty($decoded['series_id']) && !empty($decoded['contest_id']) && !empty($decoded['team_id'])) {
				$authUser	=	$this->Users->find()->where(['id'=>$decoded['user_id'],'status'=>ACTIVE])->first();
				if(!empty($authUser)) {
					$joinedContest	=	$this->PlayerTeamContests->find()
										->where(['match_id'=>$decoded['match_id'],'series_id'=>$decoded['series_id'],'contest_id'=>$decoded['contest_id'],'user_id'=>$decoded['user_id']])
										->toArray();
					if(!empty($decoded['team_id'])) {
						if(!empty($joinedContest)) {
							foreach($joinedContest as $contestRecords) {
								$this->PlayerTeamContests->delete($contestRecords);
							}
						}
						foreach($decoded['team_id'] as $teamId) {
							$joinContest['player_team_id']	=	$teamId;
							$joinContest['match_id']		=	$decoded['match_id'];
							$joinContest['series_id']		=	$decoded['series_id'];
							$joinContest['contest_id']		=	$decoded['contest_id'];
							$joinContest['user_id']			=	$decoded['user_id'];
							$playerContest	=	$this->PlayerTeamContests->newEntity();
							$this->PlayerTeamContests->patchEntity($playerContest,$joinContest);
							if($this->PlayerTeamContests->save($playerContest)) {
								$status	=	true;
							}
						}
						if($status == true) {
							$message	=	__('Team switched successfuly.',true);
						}
					}
				} else {
					$message	=	__('Invalid user id.',true);
				}
			} else {
				$message	=	__('Please check user id, match id, series id, contest id or team ids are blank.',true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function applyContestInviteCode() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Users');
		$this->loadModel('PlayerTeams');
		$this->loadModel('MatchContest');
		$this->loadModel('PlayerTeamContests');
		if(!empty($decoded)) {
			if(!empty($decoded['invite_code']) && !empty($decoded['user_id'])) {
				$contestMatch	=	$this->MatchContest->find()->where(['MatchContest.invite_code'=>$decoded['invite_code']])->contain(['SeriesSquad'=>['Series','LocalMstTeams','VisitorMstTeams'],'Contest'])->first();
				$authUser	=	$this->Users->find()->where(['id'=>$decoded['user_id'],'status'=>ACTIVE])->first();
				if(!empty($authUser)) {
					if(!empty($contestMatch)) {
						$seriesSquad	=	$contestMatch->series_squad;
						
						$totalContest	=	$this->MatchContest->find()->where(['match_id'=>$seriesSquad->id])->count();
						
						$filePath		=	WWW_ROOT.'uploads/team_flag/';
						$localTeamFlag	=	$visitorTeamFlag	=	'';
						if(!empty($seriesSquad->local_mst_team) && file_exists($filePath.$seriesSquad->local_mst_team->flag)) {
							$localTeamFlag	=	SITE_URL.'uploads/team_flag/'.$seriesSquad->local_mst_team->flag;
						}
						if(!empty($seriesSquad->visitor_mst_team) && file_exists($filePath.$seriesSquad->visitor_mst_team->flag)) {
							$visitorTeamFlag=	SITE_URL.'uploads/team_flag/'.$seriesSquad->visitor_mst_team->flag;
						}
						$matchData['series_id']			=	$seriesSquad->series_id;
						$matchData['match_id']			=	$seriesSquad->match_id;
						$matchData['series_name']		=	str_replace("Cricket ","",$seriesSquad->series->name);
						$matchData['local_team_id']		=	$seriesSquad->localteam_id;
						$matchData['local_team_name']	=	!empty($seriesSquad->local_mst_team->team_short_name) ? $seriesSquad->local_mst_team->team_short_name : $seriesSquad->localteam;
						$matchData['local_team_flag']	=	$localTeamFlag;
						$matchData['visitor_team_id']	=	$seriesSquad->visitorteam_id;
						$matchData['visitor_team_name']	=	!empty($seriesSquad->visitor_mst_team->team_short_name) ? $seriesSquad->visitor_mst_team->team_short_name : $seriesSquad->visitorteam;;
						$matchData['visitor_team_flag']	=	$visitorTeamFlag;
						$matchData['star_date']			=	$this->finalDate($seriesSquad->date);
						$matchData['star_time']			=	date('H:i',strtotime($seriesSquad->time));
						$matchData['total_contest']		=	!empty($totalContest) ? $totalContest : 0;
						if(!empty($contestMatch->contest)) {
							$matchData['prize_money']	=	$contestMatch->contest->winning_amount;
							$matchData['contest_id']	=	$contestMatch->contest->id;
							$matchData['category_id']	=	$contestMatch->contest->category_id;
							$matchData['entry_fee']		=	$contestMatch->contest->entry_fee;
							$matchData['multiple_team']	=	($contestMatch->contest->multiple_team == 'yes') ? true : false;
							$matchData['max_team_user'] = 	$contestMatch->contest->max_team_user;

							
							$myTeamIds	=	[];
							$teamsJoined=	$this->PlayerTeamContests->find()->where(['match_id'=>$seriesSquad->match_id,'contest_id'=>$contestMatch->contest->id,'user_id'=>$decoded['user_id']])->toArray();
							if(!empty($teamsJoined)) {
								foreach($teamsJoined as $joined) {
									$myTeamIds[]	=	$joined->player_team_id;
								}
							}
							$myTeamCount	=	$this->PlayerTeams->find()->where(['match_id'=>$seriesSquad->match_id,'series_id'=>$seriesSquad->series_id,'user_id'=>$decoded['user_id']])->count();
							$matchData['is_joined']		=	!empty($teamsJoined) ? true : false;
							$matchData['my_teams_count']=	$myTeamCount;
							$matchData['my_team_ids']	=	$myTeamIds;
						}
						$data1	=	$matchData;
						$status	=	true;
					} else {
						$message	=	__('The unique code looks invalid! Please check again.',true);
					}
				} else {
					$message	=	__('Invalid user id.',true);
				}
			} else {
				$message	=	__('Please check if invite code or user id is empty',true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function matchScores() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('SeriesSquad');
		$this->loadModel('Users');
		if(!empty($decoded)) {
			if(!empty($decoded['match_id']) && !empty($decoded['series_id'])) {
				$authUser	=	$this->Users->find()->where(['id'=>$decoded['user_id'],'status'=>ACTIVE])->first();
				if(!empty($authUser)) {
					$seriesMatch	=	$this->SeriesSquad->find()->where(['match_id'=>$decoded['match_id'],'series_id'=>$decoded['series_id']])->first();
					if(!empty($seriesMatch) && $seriesMatch->match_status == 'Not Started') {
						$message	=	__('Match not started Yet.',true);
					} else {
						$data['localteam_score']	=	!empty($seriesMatch) ? str_replace('/','-',$seriesMatch->localteam_score) : 0;
						$data['localteam_wicket']	=	!empty($seriesMatch) ? 0 : 0; //str_replace('/','-',$seriesMatch->localteam_score);
						$data['localteam_over']		=	!empty($seriesMatch) ? 0 : 0;
						$data['visitorteam_score']	=	!empty($seriesMatch) ? str_replace('/','-',$seriesMatch->visitorteam_score) : 0;
						$data['visitorteam_wicket']	=	!empty($seriesMatch) ? 0 : 0; //str_replace('/','-',$seriesMatch->visitorteam_score);
						$data['visitorteam_over']	=	!empty($seriesMatch) ? 0 : 0;
						$data['comment']			=	'';
					}
					$status	=	true;
					$data1	=	$data;
				} else {
					$message	=	__('Invalid user id.',true);
				}
			} else {
				$message	=	__('Please check match id or series id is blank.',true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	} 
	
	public function joinedContestMatches() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('PlayerTeamContests');
		$this->loadModel('MatchContest');
		$this->loadModel('Users');
		
		// set server Time Start
		$serverTimeZone	=	date_default_timezone_get();
		$timeZone		=	new \DateTimeZone($serverTimeZone);
		$currentDatTime	=	date('Y-m-d H:i:s');
		$time			=	new \DateTime($currentDatTime, $timeZone);
		$serverTime		=	$time->format('Y-m-d H:i:s'); 
		// set server Time End
		
		if(!empty($decoded)) {
			$currentDate	=	date('Y-m-d');
			$oneMonthDate	=	date('Y-m-d',strtotime('+4 Days'));
			$currentTime	=	date('H:i', strtotime(MATCH_DURATION));
			if(!empty($decoded['user_id'])) {
				$authUser	=	$this->Users->find()->where(['id'=>$decoded['user_id'],'status'=>ACTIVE])->first();
				if(!empty($authUser)) {
					$filter['user_id']				=	$decoded['user_id'];
					$filter['SeriesSquad.status']	=	ACTIVE;
					// $filter['Series.status']		=	ACTIVE;
					$upCommingMatch	=	$this->PlayerTeamContests->find()
										->where(['OR'=>[['SeriesSquad.date'=>$currentDate,'SeriesSquad.time >= '=>$currentTime],['SeriesSquad.date > '=>$currentDate,'SeriesSquad.date <= '=>$oneMonthDate]],$filter,'Series.status'=>ACTIVE])
										->contain(['SeriesSquad'=>['Series','LocalMstTeams','VisitorMstTeams']])
										->group(['SeriesSquad.match_id','SeriesSquad.series_id'])
										->order(['date'=>'DESC','time'=>'DESC'])->toArray();
					
					$upComingData	=	[];
					if(!empty($upCommingMatch)) {
						foreach($upCommingMatch as $key => $value) {
							$upComing	=	$value->series_squad;
							$totalContest	=	$this->PlayerTeamContests->find()->where(['match_id'=>$upComing->match_id,'series_id'=>$upComing->series_id,'user_id'=>$decoded['user_id']])->group(['contest_id'])->count();
							
							$filePath		=	WWW_ROOT.'uploads/team_flag/';
							$localTeamFlag	=	$visitorTeamFlag	=	'';
							if(!empty($upComing->local_mst_team) && file_exists($filePath.$upComing->local_mst_team->flag)) {
								$localTeamFlag	=	SITE_URL.'uploads/team_flag/'.$upComing->local_mst_team->flag;
							}
							if(!empty($upComing->visitor_mst_team) && file_exists($filePath.$upComing->visitor_mst_team->flag)) {
								$visitorTeamFlag=	SITE_URL.'uploads/team_flag/'.$upComing->visitor_mst_team->flag;
							}
							$finalDate = date("Y-m-d", strtotime($upComing->date)); 
							$seriesName	=	!empty($upComing->series->short_name) ? $upComing->series->short_name : str_replace("Cricket ","",$upComing->series->name);
							$upComingData[$key]['series_id']		=	$upComing->series_id;
							$upComingData[$key]['match_id']			=	$upComing->match_id;
							$upComingData[$key]['series_name']		=	$seriesName;
							$upComingData[$key]['local_team_id']	=	$upComing->localteam_id;
							$upComingData[$key]['local_team_name']	=	!empty($upComing->local_mst_team->team_short_name) ? $upComing->local_mst_team->team_short_name : $upComing->localteam;
							$upComingData[$key]['local_team_flag']	=	$localTeamFlag;
							$upComingData[$key]['visitor_team_id']	=	$upComing->visitorteam_id;
							$upComingData[$key]['visitor_team_name']=	!empty($upComing->visitor_mst_team->team_short_name) ? $upComing->visitor_mst_team->team_short_name : $upComing->visitorteam;;
							$upComingData[$key]['visitor_team_flag']=	$visitorTeamFlag;
							//$upComingData[$key]['star_date']		=	$upComing->date;
							$upComingData[$key]['star_date']		=	$this->finalDate($upComing->date);
							$upComingData[$key]['star_time']		=	date('H:i',strtotime($upComing->time.MATCH_DURATIONS));
							$upComingData[$key]['total_contest']	=	!empty($totalContest) ? $totalContest : 0;
							$upComingData[$key]['server_time']		=	$serverTime;
						}
					}
					
					$liveTime	=	date('H:i',strtotime(MATCH_DURATION));
					$currentTime=	date('H:i');
					/* $liveMatch	=	$this->PlayerTeamContests->find()
									// ->where(['SeriesSquad.date' => $currentDate,'OR'=>[['SeriesSquad.time >='=>$currentTime,'SeriesSquad.time <='=>$liveTime],['SeriesSquad.match_status'=>MATCH_INPROGRESS],['SeriesSquad.match_status'=>'Stumps']],$filter,'Series.status'=>ACTIVE])
									->where(['OR'=>[['SeriesSquad.date' => $currentDate,'SeriesSquad.time >='=>$currentTime,'SeriesSquad.time <='=>$liveTime],['SeriesSquad.match_status'=>MATCH_INPROGRESS],['SeriesSquad.match_status'=>'Stumps'],['SeriesSquad.match_status'=>'Delayed'],['SeriesSquad.match_status'=>'Stoped']],$filter,'Series.status'=>ACTIVE])
									->contain(['SeriesSquad'=>['Series','LocalMstTeams','VisitorMstTeams']])
									->group(['SeriesSquad.match_id','SeriesSquad.series_id'])
									->order(['date'=>'DESC','time'=>'DESC'])->toArray(); */

					$liveMatch   = $this->PlayerTeamContests->find()
					->where(['OR' => [['SeriesSquad.date' => $currentDate, 'SeriesSquad.time >=' => $currentTime, 'SeriesSquad.time <=' => $liveTime], ['SeriesSquad.match_status' => MATCH_INPROGRESS], ['SeriesSquad.match_status' => MATCH_FINISH], ['SeriesSquad.match_status' => 'Stumps'], ['SeriesSquad.match_status' => 'Delayed'], ['SeriesSquad.match_status' => 'Stoped'], ['SeriesSquad.match_status' => 'Stopped']], $filter, 'Series.status' => ACTIVE,'SeriesSquad.es_verified' => 0])
					->contain(['SeriesSquad' => ['Series', 'LocalMstTeams', 'VisitorMstTeams']])
					->group(['SeriesSquad.match_id', 'SeriesSquad.series_id'])
					->order(['date' => 'DESC', 'time' => 'DESC'])->toArray();


					$liveData	=	[];
					if(!empty($liveMatch)) {
						foreach($liveMatch as $key => $value) {
							$live	=	$value->series_squad;
							$totalContest	=	$this->PlayerTeamContests->find()->where(['match_id'=>$live->match_id,'series_id'=>$live->series_id,'user_id'=>$decoded['user_id']])->group(['contest_id'])->count();
							
							$filePath		=	WWW_ROOT.'uploads/team_flag/';
							$localTeamFlag	=	$visitorTeamFlag	=	'';
							if(!empty($live->local_mst_team) && file_exists($filePath.$live->local_mst_team->flag)) {
								$localTeamFlag	=	SITE_URL.'uploads/team_flag/'.$live->local_mst_team->flag;
							}
							if(!empty($live->visitor_mst_team) && file_exists($filePath.$live->visitor_mst_team->flag)) {
								$visitorTeamFlag=	SITE_URL.'uploads/team_flag/'.$live->visitor_mst_team->flag;
							}
							$seriesName	=	!empty($live->series->short_name) ? $live->series->short_name : str_replace("Cricket ","",$live->series->name);
							$liveData[$key]['series_id']		=	$live->series_id;
							$liveData[$key]['match_id']			=	$live->match_id;
							$liveData[$key]['series_name']		=	$seriesName;
							$liveData[$key]['local_team_id']	=	$live->localteam_id;
							$liveData[$key]['local_team_name']	=	!empty($live->local_mst_team->team_short_name) ? $live->local_mst_team->team_short_name : $live->localteam;
							$liveData[$key]['local_team_flag']	=	$localTeamFlag;
							$liveData[$key]['visitor_team_id']	=	$live->visitorteam_id;
							$liveData[$key]['visitor_team_name']=	!empty($live->visitor_mst_team->team_short_name) ? $live->visitor_mst_team->team_short_name : $live->visitorteam;
							$liveData[$key]['visitor_team_flag']=	$visitorTeamFlag;
							$liveData[$key]['star_date']		=	$this->finalDate($live->date);
							$liveData[$key]['star_time']		=	$live->time;
							$liveData[$key]['total_contest']	=	!empty($totalContest) ? $totalContest : 0;
							$liveData[$key]['server_time']		=	$serverTime;
							$liveData[$key]['match_status']      =  ( $live->match_status == MATCH_FINISH && $live->es_verified == 0 ) ? 'In Review' : 'In Progress';
						}
					}
					
					$completeDate	=	date('Y-m-d',strtotime('-1 week'));
					/* $completeMatch	=	$this->PlayerTeamContests->find()
										//->where(['SeriesSquad.date >=' => $completeDate,'SeriesSquad.date <=' => $currentDate,'SeriesSquad.match_status'=>MATCH_FINISH,$filter])//
										//->where(['SeriesSquad.match_status'=>MATCH_FINISH,$filter])
										->where(['OR'=>[['SeriesSquad.match_status'=>MATCH_FINISH],['SeriesSquad.match_status'=>MATCH_CANCELLED]],$filter])
										->contain(['SeriesSquad'=>['Series','LocalMstTeams','VisitorMstTeams']])
										->group(['SeriesSquad.match_id','SeriesSquad.series_id'])
										->order(['date'=>'DESC','time'=>'DESC'])->toArray(); */


					$completeMatch = $this->PlayerTeamContests->find()
                        //->where(['SeriesSquad.match_status' => MATCH_FINISH, $filter,'SeriesSquad.es_verified' => 1])
                        //->orWhere(['SeriesSquad.match_status' => MATCH_CANCELLED,'SeriesSquad.es_verified' => 1])

                        ->where(['OR'=>[['SeriesSquad.match_status' => MATCH_FINISH],['SeriesSquad.match_status' => MATCH_CANCELLED]], $filter,'SeriesSquad.es_verified' => 1])
                        ->contain(['SeriesSquad' => ['Series', 'LocalMstTeams', 'VisitorMstTeams']])
                        ->group(['SeriesSquad.match_id', 'SeriesSquad.series_id'])
                        ->order(['date' => 'DESC', 'time' => 'DESC'])->toArray();
					
					$finishData	=	[];
					if(!empty($completeMatch)) {
						foreach($completeMatch as $key => $value) {
							$live	=	$value->series_squad;
							$totalContest	=	$this->PlayerTeamContests->find()->where(['match_id'=>$live->match_id,'series_id'=>$live->series_id,'user_id'=>$decoded['user_id']])->group(['contest_id'])->count();
							
							$filePath		=	WWW_ROOT.'uploads/team_flag/';
							$localTeamFlag	=	$visitorTeamFlag	=	'';
							if(!empty($live->local_mst_team) && file_exists($filePath.$live->local_mst_team->flag)) {
								$localTeamFlag	=	SITE_URL.'uploads/team_flag/'.$live->local_mst_team->flag;
							}
							if(!empty($live->visitor_mst_team) && file_exists($filePath.$live->visitor_mst_team->flag)) {
								$visitorTeamFlag=	SITE_URL.'uploads/team_flag/'.$live->visitor_mst_team->flag;
							}
							$seriesName	=	!empty($live->series->short_name) ? $live->series->short_name : str_replace("Cricket ","",$live->series->name);
							$finishData[$key]['series_id']			=	$live->series_id;
							$finishData[$key]['match_id']			=	$live->match_id;
							$finishData[$key]['series_name']		=	$seriesName;
							$finishData[$key]['local_team_id']		=	$live->localteam_id;
							$finishData[$key]['local_team_name']	=	!empty($live->local_mst_team->team_short_name) ? $live->local_mst_team->team_short_name : $live->localteam;
							$finishData[$key]['local_team_flag']	=	$localTeamFlag;
							$finishData[$key]['visitor_team_id']	=	$live->visitorteam_id;
							$finishData[$key]['visitor_team_name']	=	!empty($live->visitor_mst_team->team_short_name) ? $live->visitor_mst_team->team_short_name : $live->visitorteam;
							$finishData[$key]['visitor_team_flag']	=	$visitorTeamFlag;
							$finishData[$key]['star_date']			=	$this->finalDate($live->date);
							$finishData[$key]['star_time']			=	$live->time;
							$finishData[$key]['total_contest']		=	!empty($totalContest) ? $totalContest : 0;
							$finishData[$key]['server_time']		=	$serverTime;
						}
					}
					$data1->upcoming_match	=	$upComingData;
					$data1->live_match		=	$liveData;
					$data1->completed_match	=	$finishData;
					$data1->server_time		=	$serverTime;
					$status	=	true;
				} else {
					$message	=	__('Invalid user id.',true);
				}
			} else {
				$message	=	__('Please check user id is blank.',true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}

	public function joinedMatchesUpcoming() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('PlayerTeamContests');
		$this->loadModel('MatchContest');
		$this->loadModel('Users');
		
		// set server Time Start
		$serverTimeZone	=	date_default_timezone_get();
		$timeZone		=	new \DateTimeZone($serverTimeZone);
		$currentDatTime	=	date('Y-m-d H:i:s');
		$time			=	new \DateTime($currentDatTime, $timeZone);
		$serverTime		=	$time->format('Y-m-d H:i:s'); 
		// set server Time End
		
		if(!empty($decoded)) {
			$currentDate	=	date('Y-m-d');
			$oneMonthDate	=	date('Y-m-d',strtotime('+4 Days'));
			$currentTime	=	date('H:i', strtotime(MATCH_DURATION));

			if(!empty($decoded['user_id'])) {

				$authUser	=	$this->Users->find()->select(['id'])->where(['id'=>$decoded['user_id'],'status'=>ACTIVE])->first();
				if(!empty($authUser)) {

					$filter['user_id']				=	$decoded['user_id'];
					$filter['SeriesSquad.status']	=	ACTIVE;
					// $filter['Series.status']		=	ACTIVE;
					$upCommingMatch	=	$this->PlayerTeamContests->find()
										->where(['OR'=>[['SeriesSquad.date'=>$currentDate,'SeriesSquad.time >= '=>$currentTime],['SeriesSquad.date > '=>$currentDate,'SeriesSquad.date <= '=>$oneMonthDate]],$filter,'Series.status'=>ACTIVE])
										->contain(['SeriesSquad'=>['Series','LocalMstTeams','VisitorMstTeams']])
										->group(['SeriesSquad.match_id','SeriesSquad.series_id'])
										->order(['date'=>'DESC','time'=>'DESC'])->toArray();
					
					// Get contest wise counting
					$query = $this->PlayerTeamContests->find('list', ['keyField'=>'match_id','valueField'=>'count']);
					$query->select(['count' => $query->func()->count('id'), 'match_id']);
					$query->where([ 'user_id'=>$decoded['user_id'] ]);
					$teamsJoinedContestMatchWise	=	$query->group(['match_id'])->toArray();
					//pr($teamsJoinedContestMatchWise);die;
					
					//pr($upCommingMatch);die;
					$upComingData	=	[];
					if(!empty($upCommingMatch)) {
						foreach($upCommingMatch as $key => $value) {
							$upComing	=	$value->series_squad;
							

							$totalContest	=	(!empty($teamsJoinedContestMatchWise[$upComing->match_id])) ? $teamsJoinedContestMatchWise[$upComing->match_id] : 0;
							
							$filePath		=	WWW_ROOT.'uploads/team_flag/';
							$localTeamFlag	=	$visitorTeamFlag	=	'';
							if(!empty($upComing->local_mst_team) && file_exists($filePath.$upComing->local_mst_team->flag)) {
								$localTeamFlag	=	SITE_URL.'uploads/team_flag/'.$upComing->local_mst_team->flag;
							}
							if(!empty($upComing->visitor_mst_team) && file_exists($filePath.$upComing->visitor_mst_team->flag)) {
								$visitorTeamFlag=	SITE_URL.'uploads/team_flag/'.$upComing->visitor_mst_team->flag;
							}
							$finalDate = date("Y-m-d", strtotime($upComing->date)); 
							$seriesName	=	!empty($upComing->series->short_name) ? $upComing->series->short_name : str_replace("Cricket ","",$upComing->series->name);
							$upComingData[$key]['series_id']		=	$upComing->series_id;
							$upComingData[$key]['match_id']			=	$upComing->match_id;
							$upComingData[$key]['series_name']		=	$seriesName;
							$upComingData[$key]['local_team_id']	=	$upComing->localteam_id;
							$upComingData[$key]['local_team_name']	=	!empty($upComing->local_mst_team->team_short_name) ? $upComing->local_mst_team->team_short_name : $upComing->localteam;
							$upComingData[$key]['local_team_flag']	=	$localTeamFlag;
							$upComingData[$key]['visitor_team_id']	=	$upComing->visitorteam_id;
							$upComingData[$key]['visitor_team_name']=	!empty($upComing->visitor_mst_team->team_short_name) ? $upComing->visitor_mst_team->team_short_name : $upComing->visitorteam;;
							$upComingData[$key]['visitor_team_flag']=	$visitorTeamFlag;
							//$upComingData[$key]['star_date']		=	$upComing->date;
							$upComingData[$key]['star_date']		=	$this->finalDate($upComing->date);
							$upComingData[$key]['star_time']		=	date('H:i',strtotime($upComing->time.MATCH_DURATIONS));
							$upComingData[$key]['total_contest']	=	!empty($totalContest) ? $totalContest : 0;
							$upComingData[$key]['server_time']		=	$serverTime;
						}
					}
					
					$data1->upcoming_match	=	$upComingData;
					$data1->server_time		=	$serverTime;
					
					$status	=	true;
				} else {
					$message	=	__('Invalid user id.',true);
				}
			} else {
				$message	=	__('Please check user id is blank.',true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'tokenexpire'=>0,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}

	public function joinedMatchesLive() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('PlayerTeamContests');
		$this->loadModel('MatchContest');
		$this->loadModel('Users');
		
		// set server Time Start
		$serverTimeZone	=	date_default_timezone_get();
		$timeZone		=	new \DateTimeZone($serverTimeZone);
		$currentDatTime	=	date('Y-m-d H:i:s');
		$time			=	new \DateTime($currentDatTime, $timeZone);
		$serverTime		=	$time->format('Y-m-d H:i:s'); 
		// set server Time End
		
		if(!empty($decoded)) {
			$currentDate	=	date('Y-m-d');
			$oneMonthDate	=	date('Y-m-d',strtotime('+4 Days'));
			$currentTime	=	date('H:i', strtotime(MATCH_DURATION));

			if(!empty($decoded['user_id'])) {

				$authUser	=	$this->Users->find()->select(['id'])->where(['id'=>$decoded['user_id'],'status'=>ACTIVE])->first();
				if(!empty($authUser)) {

					$filter['user_id']				=	$decoded['user_id'];
					$filter['SeriesSquad.status']	=	ACTIVE;
					
					$liveTime	=	date('H:i',strtotime(MATCH_DURATION));
					$currentTime=	date('H:i');
					
					$liveMatch   = $this->PlayerTeamContests->find()
					->select(['PlayerTeamContests.id','SeriesSquad.id','SeriesSquad.match_id','SeriesSquad.series_id','SeriesSquad.date','SeriesSquad.time','SeriesSquad.localteam_id','SeriesSquad.localteam','SeriesSquad.visitorteam_id','SeriesSquad.visitorteam'])
					->where(['OR' => [['SeriesSquad.date' => $currentDate, 'SeriesSquad.time >=' => $currentTime, 'SeriesSquad.time <=' => $liveTime], ['SeriesSquad.match_status' => MATCH_INPROGRESS], ['SeriesSquad.match_status' => MATCH_FINISH], ['SeriesSquad.match_status' => 'Stumps'], ['SeriesSquad.match_status' => 'Delayed'], ['SeriesSquad.match_status' => 'Stoped'], ['SeriesSquad.match_status' => 'Stopped']], $filter, 'Series.status' => ACTIVE,'SeriesSquad.es_verified' => 0])
					->contain(['SeriesSquad' => ['Series'=>['fields'=>['name','short_name']],'LocalMstTeams'=>['fields'=>['flag','team_short_name']],'VisitorMstTeams'=>['fields'=>['flag','team_short_name']]]])
					->group(['SeriesSquad.match_id', 'SeriesSquad.series_id'])
					->order(['date' => 'DESC', 'time' => 'DESC'])->toArray();

					// Get contest wise counting
					$query = $this->PlayerTeamContests->find('list', ['keyField'=>'match_id','valueField'=>'count']);
					$query->select(['count' => $query->func()->count('id'), 'match_id']);
					$query->where([ 'user_id'=>$decoded['user_id'] ]);
					$teamsJoinedContestMatchWise	=	$query->group(['match_id'])->toArray();
					//pr($teamsJoinedContestMatchWise);die;
					
					$liveData	=	[];
					if(!empty($liveMatch)) {
						foreach($liveMatch as $key => $value) {
							$live	=	$value->series_squad;

							$totalContest	=	(!empty($teamsJoinedContestMatchWise[$live->match_id])) ? $teamsJoinedContestMatchWise[$live->match_id] : 0;
							
							$filePath		=	WWW_ROOT.'uploads/team_flag/';
							$localTeamFlag	=	$visitorTeamFlag	=	'';
							if(!empty($live->local_mst_team) && file_exists($filePath.$live->local_mst_team->flag)) {
								$localTeamFlag	=	SITE_URL.'uploads/team_flag/'.$live->local_mst_team->flag;
							}
							if(!empty($live->visitor_mst_team) && file_exists($filePath.$live->visitor_mst_team->flag)) {
								$visitorTeamFlag=	SITE_URL.'uploads/team_flag/'.$live->visitor_mst_team->flag;
							}
							$seriesName	=	!empty($live->series->short_name) ? $live->series->short_name : str_replace("Cricket ","",$live->series->name);
							$liveData[$key]['series_id']		=	$live->series_id;
							$liveData[$key]['match_id']			=	$live->match_id;
							$liveData[$key]['series_name']		=	$seriesName;
							$liveData[$key]['local_team_id']	=	$live->localteam_id;
							$liveData[$key]['local_team_name']	=	!empty($live->local_mst_team->team_short_name) ? $live->local_mst_team->team_short_name : $live->localteam;
							$liveData[$key]['local_team_flag']	=	$localTeamFlag;
							$liveData[$key]['visitor_team_id']	=	$live->visitorteam_id;
							$liveData[$key]['visitor_team_name']=	!empty($live->visitor_mst_team->team_short_name) ? $live->visitor_mst_team->team_short_name : $live->visitorteam;
							$liveData[$key]['visitor_team_flag']=	$visitorTeamFlag;
							$liveData[$key]['star_date']		=	$this->finalDate($live->date);
							$liveData[$key]['star_time']		=	$live->time;
							$liveData[$key]['total_contest']	=	!empty($totalContest) ? $totalContest : 0;
							$liveData[$key]['server_time']		=	$serverTime;
							$liveData[$key]['match_status']      =  ( $live->match_status == MATCH_FINISH && $live->es_verified == 0 ) ? 'In Review' : 'In Progress';
						}
					}

					$data1->live_match		=	$liveData;
					$data1->server_time		=	$serverTime;
					
					$status	=	true;
				} else {
					$message	=	__('Invalid user id.',true);
				}
			} else {
				$message	=	__('Please check user id is blank.',true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'tokenexpire'=>0,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}

	public function joinedContestList() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('PlayerTeamContests');
		$this->loadModel('Users');
		//$this->loadModel('Contest');
		$this->loadModel('PlayerTeams');
		$this->loadModel('MatchContest');
		$this->loadModel('SeriesSquad');
		$this->loadModel('CustomBreakupmain');

		if(!empty($decoded)) {
			if(!empty($decoded['user_id']) && !empty($decoded['match_id']) && !empty($decoded['series_id'])) {

				$authUser	=	$this->Users->find()->select(['id'])->where(['id'=>$decoded['user_id'],'status'=>ACTIVE])->count();

				$trump_mode = ( isset($decoded['trump_mode']) ) ? $decoded['trump_mode'] : '';

				$trum_condition = [];
				if( $trump_mode !='' ){
					$trum_condition['PlayerTeams.trump_mode'] = $trump_mode;
				}
				
				if($authUser) {
					$seriesSquad	=	$this->SeriesSquad->find()->select(['id'])->where(['series_id'=>$decoded['series_id'],'match_id'=>$decoded['match_id'],'match_status'=>MATCH_NOTSTART])->first();
					
					if(!empty($seriesSquad)) {
						$joinedTeams=	$this->PlayerTeamContests->find()
										->select([
											'PlayerTeamContests.id','PlayerTeamContests.rank','PlayerTeams.points','PlayerTeams.trump_mode','Contest.id','Contest.contest_type','Contest.confirmed_winning','Contest.entry_fee','Contest.winning_amount','Contest.contest_size','Contest.category_id','Contest.multiple_team','Contest.usable_bonus_percentage','Contest.is_adjustable'
										])
										->where(['PlayerTeamContests.user_id'=>$decoded['user_id'],'PlayerTeamContests.match_id'=>$decoded['match_id'],'PlayerTeamContests.series_id'=>$decoded['series_id'],$trum_condition ])
										->group(['PlayerTeamContests.contest_id'])
										->contain(['PlayerTeams','Contest'=>['CustomBreakup'=>['fields'=>['contest_id','start','end','name','price']]]])
										->toArray();
					} else {
						$joinedTeams=	$this->PlayerTeamContests->find()
										->select([
											'PlayerTeamContests.id','PlayerTeamContests.rank','PlayerTeamContests.contest_id','PlayerTeams.points','PlayerTeams.trump_mode','Contest.id','Contest.contest_type','Contest.confirmed_winning','Contest.entry_fee','Contest.winning_amount','Contest.contest_size','Contest.category_id','Contest.multiple_team','Contest.usable_bonus_percentage','Contest.is_adjustable'
										])
										->where(['PlayerTeamContests.user_id'=>$decoded['user_id'],'PlayerTeamContests.match_id'=>$decoded['match_id'],'PlayerTeamContests.series_id'=>$decoded['series_id'],$trum_condition ])
										->order(['PlayerTeams.points'=>'DESC'])
										->contain(['PlayerTeams','Contest'=>['CustomBreakup']])
										->toArray();
										
						if(!empty($joinedTeams)) {
							foreach($joinedTeams as $teams) {
								$pointsData[$teams->contest_id][]	=	$teams->player_team->points;
							}
						}
						if(!empty($joinedTeams)) {
							foreach($joinedTeams as $key => $teams) {
								if($teams->player_team->points != $pointsData[$teams->contest_id][0]) {
									unset($joinedTeams[$key]);
								}
							}
						}
					}
					
					$contest		=	[];
					$upComingData	=	[];
					$myTeamRank 	=	[];
					$pointsData		=	[];
					

					// Get contest wise counting
					$query = $this->PlayerTeamContests->find('list', ['keyField'=>'contest_id','valueField'=>'count']);
					$query->select(['count' => $query->func()->count('id'), 'contest_id']);
					$query->where([ 'match_id'=>$decoded['match_id'] ]);
					$teamsJoinedContestWise	=	$query->group(['contest_id'])->toArray();
					

					// Get user joined team id
					$teamsJoined		=	$this->PlayerTeamContests->find()
					->select(['PlayerTeamContests.id','PlayerTeamContests.contest_id','PlayerTeamContests.player_team_id','PlayerTeamContests.winning_amount','PlayerTeams.id','PlayerTeams.team_count'])
					->where(['PlayerTeamContests.match_id'=>$decoded['match_id'],'PlayerTeamContests.user_id'=>$decoded['user_id']])
					->contain(['PlayerTeams'])
					->toArray();
					
				
					$myTeamIdsArr	=	[];
					$myTeamNoArr	=	[];
					$winningAmtArr	=	[];
					if(!empty($teamsJoined)){
						foreach($teamsJoined AS $joined){
							$myTeamIdsArr[$joined->contest_id][]	=	$joined->player_team_id;
							$myTeamNoArr[$joined->contest_id][]	=	!empty($joined->player_team) ? $joined->player_team->team_count : 0;
							$winningAmtArr[$joined->contest_id][]	=	!empty($joined->winning_amount) ? $joined->winning_amount : 0;
						}
					}
					
					
					if(!empty($joinedTeams)) {
						$contestKey	=	0;
						foreach($joinedTeams as $conKey => $contestValue) {
							// pr($contestValue->contest->contest_type);
							$inviteCode	=	$this->getInviteCode($decoded['match_id'],$contestValue->contest->id);
							$myTeamRank[] = $contestValue->rank;
							
							// price breakup for perticular contest
							$customBreakup	=	end($contestValue->contest->custom_breakup);
							if(!empty($customBreakup) &&!empty($customBreakup->end)) {
								$toalWinner	=	$customBreakup->end;
							} else {
								$toalWinner	=	!empty($customBreakup) ? $customBreakup->start : 0;
							}
							
							$joinedTeamCount	=	(!empty($teamsJoinedContestWise[$contestValue->contest->id])) ? $teamsJoinedContestWise[$contestValue->contest->id] : 0;
							
							$myTeamIds	=	[];
							$myTeamNo	=	[];
							$winningAmt	=	[];
							if(!empty($myTeamIdsArr)){
								$myTeamIds	=	( !empty($myTeamIdsArr[$contestValue->contest->id]) ) ? $myTeamIdsArr[$contestValue->contest->id] : [];
							}
							if(!empty($myTeamNoArr)){
								$myTeamNo	=	( !empty($myTeamNoArr[$contestValue->contest->id]) ) ? $myTeamNoArr[$contestValue->contest->id] : [];
							}
							if(!empty($winningAmtArr)){
								$winningAmt	=	( !empty($winningAmtArr[$contestValue->contest->id]) ) ? $winningAmtArr[$contestValue->contest->id] : [];
							}


							$customPrice	=	[];
							$isWinner		=	false;
							$first_prize = '';
							if(!empty($contestValue->contest->custom_breakup)) {
								foreach($contestValue->contest->custom_breakup as $key=> $customBreakup) {
									// $isWinner		=	false;
									if(($contestValue->rank >= $customBreakup->start && $contestValue->rank <= $customBreakup->end) || $contestValue->rank == $customBreakup->end) {
										$isWinner	=	true;
									}
									if($customBreakup->start == $customBreakup->end) {
										$customPrice[$key]['rank']	=	'Rank '.$customBreakup->start;
									} else {
										$customPrice[$key]['rank']	=	$customBreakup->name;
									}
									// $customPrice[$key]['rank']	=	$customBreakup->name;
									$customPrice[$key]['price']	=	$customBreakup->price;
									if( $first_prize == '' ){
										$first_prize = $customBreakup->price;
									}
								}
							} else if(strpos($contestValue->contest->contest_type,'free') != false && $contestValue->rank == 1) {
								$isWinner	=	true;
							}

							$customPricemain	=	[];
                            $winning_amount_maximum = 0;
							if ( $contestValue->contest->is_adjustable ) {
								$custom_breakupmain = $this->CustomBreakupmain->find()
								->where(['contest_id'=>$contestValue->contest->id, 'match_id'=>$decoded['match_id']])
								->toArray();
								if(!empty($custom_breakupmain)) {
                                    $isWinner    = false;
									foreach($custom_breakupmain as $key=> $customBreakup) {

                                        if (($contestValue->rank >= $customBreakup->start && $contestValue->rank <= $customBreakup->end) || $contestValue->rank == $customBreakup->end) {
                                            $isWinner = true;
                                        }

										if($customBreakup->start == $customBreakup->end) {
											$customPricemain[$key]['rank']	=	'Rank '.$customBreakup->start;
										} else {
											$customPricemain[$key]['rank']	=	$customBreakup->name;
										}
                                        $customPricemain[$key]['price']	=	$customBreakup->price;
                                        
                                        //Calculate Prize Pool
                                        $levelWinner = ( $customBreakup->end - ($customBreakup->start-1) );
                                        $levelPrize = ($levelWinner * $customBreakup->price);
                                        $winning_amount_maximum += $levelPrize;

									}
								}
							}
							
							if($contestValue->contest->confirmed_winning=='' || $contestValue->contest->confirmed_winning=='0'){
								$winComfimed = 'no';
							}else{
								$winComfimed = $contestValue->contest->confirmed_winning;
							}

							$dynamic_contest_message = '';
                            if( $contestValue->contest->is_adjustable ){
                                $dynamic_contest_message = DYNAMIC_CONTEST_MESSAGE;
							}

							$contest[$contestKey]['confirm_winning']=	$winComfimed;
							$contest[$contestKey]['entry_fee']		=	$contestValue->contest->entry_fee;
							$contest[$contestKey]['prize_money']	=	$contestValue->contest->winning_amount;
							$contest[$contestKey]['total_teams']	=	$contestValue->contest->contest_size;
							$contest[$contestKey]['category_id']	=	$contestValue->contest->category_id;
							$contest[$contestKey]['contest_id']		=	$contestValue->contest->id;
							$contest[$contestKey]['total_winners']	=	(int) $toalWinner;
							$contest[$contestKey]['teams_joined']	=	$joinedTeamCount;
							$contest[$contestKey]['is_joined']		=	!empty($myTeamIds) ? true : false;
							$contest[$contestKey]['multiple_team']	=	($contestValue->contest->multiple_team == 'yes') ? true : false;
							$contest[$contestKey]['max_team_user']  = 	$contestValue->contest->max_team_user;
							$contest[$contestKey]['usable_bonus_percentage']	=	$contestValue->contest->usable_bonus_percentage;
							$contest[$contestKey]['invite_code']	=	!empty($inviteCode)? $inviteCode->invite_code : '';
							$contest[$contestKey]['breakup_detail']	=	$customPrice;
							$contest[$contestKey]['my_team_ids']	=	$myTeamIds;
							$contest[$contestKey]['team_number']	=	$myTeamNo;
							$contest[$contestKey]['points_earned']	=	!empty($contestValue->player_team)? $contestValue->player_team->points : 0;
							$contest[$contestKey]['my_rank']		=	$contestValue->rank;
							$contest[$contestKey]['is_winner']		=	$isWinner;
							$contest[$contestKey]['winning_amount']	=	array_sum($winningAmt);
							$contest[$contestKey]['trump_mode']		=	!empty($contestValue->player_team)? $contestValue->player_team->trump_mode : 0;
							$contest[$contestKey]['winning_amount_maximum']  = (string)$winning_amount_maximum;
							$contest[$contestKey]['dynamic_contest_message']  = $dynamic_contest_message;
							$contest[$contestKey]['is_adjustable']	 = $contestValue->contest->is_adjustable;
							$contest[$contestKey]['breakup_detail_maximum']	=	$customPricemain;
							$contest[$contestKey]['first_prize']        = (int)$first_prize;
							$contestKey++;
						}
					} else {
						/* $currentDate	=	date('Y-m-d');
						$oneMonthDate	=	date('Y-m-d',strtotime('+4 Days'));
						$currentTime	=	date('H:i', strtotime('+30 min'));
						$upCommingMatch	=	$this->SeriesSquad->find()
						->select(['SeriesSquad.id','SeriesSquad.series_id','SeriesSquad.match_id','SeriesSquad.localteam_id','SeriesSquad.localteam','SeriesSquad.visitorteam_id','SeriesSquad.visitorteam','SeriesSquad.date','SeriesSquad.time','LocalMstTeams.id','LocalMstTeams.flag','LocalMstTeams.team_short_name','VisitorMstTeams.id','VisitorMstTeams.flag','VisitorMstTeams.team_short_name','Series.id','Series.name','Series.short_name'])
						->where(['OR'=>[['date'=>$currentDate,'time >= '=>$currentTime],['date > '=>$currentDate,'date <= '=>$oneMonthDate,'Series.status'=>ACTIVE,'SeriesSquad.status'=>ACTIVE]]])
						->contain(['Series','LocalMstTeams','VisitorMstTeams'])
						->order(['date','time'])
						->limit(3)
						->toArray();
						
						if(!empty($upCommingMatch)) {
							foreach($upCommingMatch as $key => $upComing) {
								$totalContest	=	$this->MatchContest->find()->where(['match_id'=>$upComing->id])->count();
								
								$filePath		=	WWW_ROOT.'uploads/team_flag/';
								$localTeamFlag	=	$visitorTeamFlag	=	'';
								if(!empty($upComing->local_mst_team->flag) && file_exists($filePath.$upComing->local_mst_team->flag)) {
									$localTeamFlag	=	SITE_URL.'uploads/team_flag/'.$upComing->local_mst_team->flag;
								}
								if(!empty($upComing->visitor_mst_team->flag) && file_exists($filePath.$upComing->visitor_mst_team->flag)) {
									$visitorTeamFlag=	SITE_URL.'uploads/team_flag/'.$upComing->visitor_mst_team->flag;
								}
								$seriesName	=	!empty($upComing->series->short_name) ? $upComing->series->short_name : str_replace("Cricket ","",$upComing->series->name);
								$upComingData[$key]['series_id']		=	$upComing->series_id;
								$upComingData[$key]['match_id']			=	$upComing->match_id;
								$upComingData[$key]['series_name']		=	$seriesName;
								$upComingData[$key]['local_team_id']	=	$upComing->localteam_id;
								$upComingData[$key]['local_team_name']	=	!empty($upComing->local_mst_team->team_short_name) ? $upComing->local_mst_team->team_short_name : $upComing->localteam;
								$upComingData[$key]['local_team_flag']	=	$localTeamFlag;
								$upComingData[$key]['visitor_team_id']	=	$upComing->visitorteam_id;
								$upComingData[$key]['visitor_team_name']=	!empty($upComing->visitor_mst_team->team_short_name) ? $upComing->visitor_mst_team->team_short_name : $upComing->visitorteam;;
								$upComingData[$key]['visitor_team_flag']=	$visitorTeamFlag;
								$upComingData[$key]['star_date']		=	$this->finalDate($upComing->date);
								$upComingData[$key]['star_time']		=	date('H:i',strtotime($upComing->time.'-30 min'));
								$upComingData[$key]['total_contest']	=	!empty($totalContest) ? $totalContest : 0;
							}
						} */
					}
					// team count how team are created for perticular match.
					$myTeams	=	$this->PlayerTeams->find()
									->where(['user_id'=>$decoded['user_id'],'match_id'=>$decoded['match_id'],'series_id'=>$decoded['series_id']])->count();
					
					$status	=	true;
					$data1->joined_contest	=	$contest;
					$data1->upcoming_match	=	$upComingData;
					$data1->my_team_count	=	$myTeams;
					$data1->my_team_rank	=	$myTeamRank;
				} else {
					$message	=	__('Invalid user id.',true);
				}
			} else {
				$message	=	__("user id, match id or contest id are empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'tokenexpire'=>0,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}

	public function contestDetail() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Contest');
		$this->loadModel('PlayerTeams');
		$this->loadModel('PlayerTeamContests');
		$this->loadModel('CustomBreakupmain');
		
		// set server Time Start
		$serverTimeZone	=	date_default_timezone_get();
		$timeZone		=	new \DateTimeZone($serverTimeZone);
		$currentDatTime	=	date('Y-m-d H:i:s');
		$time			=	new \DateTime($currentDatTime, $timeZone);
		$serverTime		=	$time->format('Y-m-d H:i:s'); 
		// set server Time End
		
		if(!empty($decoded)) {
			if(!empty($decoded['match_id']) && !empty($decoded['contest_id']) && !empty($decoded['series_id'])) {

				$contestDetail	=	$this->Contest->find()
				->select(['id','is_adjustable','confirmed_winning','winning_amount','contest_size','entry_fee','multiple_team','max_team_user','usable_bonus_percentage'])
				->where(['id'=>$decoded['contest_id']])
				->contain(['CustomBreakup'])
				->first();

				$prizeMoney	=	$totalTeams	=	$teamsJoined	=	$toalWinner	=	$entryfee	=	0;
				$inviteCode	=	'';
				$teamData	=	$myTeamIds	=	$customPrice	=	[];

				if(!empty($contestDetail)) {
					$inviteCode	=	$this->getInviteCode($decoded['match_id'],$decoded['contest_id']);
					
					$customBreakup	=	end($contestDetail->custom_breakup);
					if(!empty($customBreakup) &&!empty($customBreakup->end)) {
						$toalWinner	=	$customBreakup->end;
					} else {
						$toalWinner	=	!empty($customBreakup) ? $customBreakup->start : 0;
					}
					
					$first_prize = '';
					if(!empty($contestDetail->custom_breakup)) {
						foreach($contestDetail->custom_breakup as $key=> $customBreakup) {
							if($customBreakup->start == $customBreakup->end) {
								$customPrice[$key]['rank']	=	'Rank '.$customBreakup->start;
							} else {
								$customPrice[$key]['rank']	=	$customBreakup->name;
							}
							$customPrice[$key]['price']	=	$customBreakup->price;
							if( $first_prize == '' ){
								$first_prize = $customBreakup->price;
							}
						}
					}

					$customPricemain	=	[];
                    $winning_amount_maximum = 0;
					if ( $contestDetail->is_adjustable ) {
						$custom_breakupmain = $this->CustomBreakupmain->find()
						->where(['contest_id'=>$contestDetail->id, 'match_id'=>$decoded['match_id']])
						->toArray();
						if(!empty($custom_breakupmain)) {
							foreach($custom_breakupmain as $key=> $customBreakup) {
								if($customBreakup->start == $customBreakup->end) {
									$customPricemain[$key]['rank']	=	'Rank '.$customBreakup->start;
								} else {
									$customPricemain[$key]['rank']	=	$customBreakup->name;
								}
                                $customPricemain[$key]['price']	=	$customBreakup->price;
                                
                                //Calculate Prize Pool
                                $levelWinner = ( $customBreakup->end - ($customBreakup->start-1) );
                                $levelPrize = ($levelWinner * $customBreakup->price);
                                $winning_amount_maximum += $levelPrize;

							}
						}
					}
					
					$myTeams	=	$this->PlayerTeamContests->find()
										->select(['PlayerTeamContests.id','PlayerTeamContests.player_team_id','PlayerTeamContests.rank','PlayerTeamContests.previous_rank','PlayerTeamContests.winning_amount'])
										->where(['PlayerTeamContests.match_id'=>$decoded['match_id'],'PlayerTeamContests.contest_id'=>$decoded['contest_id'],'PlayerTeamContests.user_id'=>$decoded['user_id']])
										->contain(['PlayerTeams'=>['fields'=>['team_count','id','points'],'PlayerTeamDetails'=>['fields'=>['id','player_id','player_team_id']]],'Users'=>['fields'=>['team_name','id','image']]])
										->order(['PlayerTeamContests.user_id'])->toArray();

					$allTeams	=	$this->PlayerTeamContests->find()
										->select(['PlayerTeamContests.id','PlayerTeamContests.player_team_id','PlayerTeamContests.rank','PlayerTeamContests.previous_rank','PlayerTeamContests.winning_amount'])
										->where(['PlayerTeamContests.match_id'=>$decoded['match_id'],'PlayerTeamContests.contest_id'=>$decoded['contest_id'],'PlayerTeamContests.user_id !='=>$decoded['user_id']])
										->contain(['PlayerTeams'=>['fields'=>['team_count','id','points'],'PlayerTeamDetails'=>['fields'=>['id','player_id','player_team_id']]],'Users'=>['fields'=>['team_name','id','image']]])
										->order(['PlayerTeamContests.user_id'])->toArray();

					$mergedTeam	=	array_merge($myTeams,$allTeams);
					
					if(!empty($mergedTeam)) {
						$teamCount	=	0;
						foreach($mergedTeam as $userTeam) {
							/* $player_ids = array();
							$player_ids_array = $userTeam->player_team->player_team_details;
							foreach ($player_ids_array as $row) {
								$player_ids[] = $row->player_id;
							} */
							
							$winAmount		=	!empty($userTeam->winning_amount) ? $userTeam->winning_amount : 0;
							
							if(!empty($userTeam->user)) {
								$teamData[$teamCount]['user_id']			=	$userTeam->user->id;
								$teamData[$teamCount]['team_name']			=	$userTeam->user->team_name;
								$teamData[$teamCount]['team_no']			=	!empty($userTeam->player_team) ? $userTeam->player_team->team_count : 0;
								$teamData[$teamCount]['rank']				=	$userTeam->rank;
								$teamData[$teamCount]['previous_rank']		=	$userTeam->previous_rank;
								$teamData[$teamCount]['point']				=	$userTeam->player_team->points;
								$teamData[$teamCount]['substitute_status']	=	'';
								$teamData[$teamCount]['winning_amount']		=	$winAmount;
								
								$teamCount++;
							}
						}
					}
					
					// re-arrange team array according team rank
					$ranArr	= $MyUser =	[];
					if(!empty($teamData)) {
						foreach($teamData as $key => $teamss) {
							if($teamss['user_id'] == $decoded['user_id']) {
								$MyUser[]	=	$teamss;
								unset($teamData[$key]);
							}
						}
					}
					array_values($teamData);
					if(!empty($teamData)) {
						foreach($teamData as $key => $teamss) {
							$ranArr[$key]	=	$teamss['rank'];
						}
					}
					array_multisort($ranArr, SORT_ASC, $teamData);
					$teamRankData	=	array_merge($MyUser,$teamData);
					
					// Teams that I have joined with current contest
					/* $teamsJoined	=	$this->PlayerTeamContests->find('all')
										->where(['match_id'=>$decoded['match_id'],'contest_id'=>$decoded['contest_id'],'user_id'=>$decoded['user_id']])->toArray();
					if(!empty($teamsJoined)) {
						foreach($teamsJoined as $joined) {
							$myTeamIds[]	=	$joined->player_team_id;
						}
					} */

					if(!empty($myTeams)) {
						foreach($myTeams as $joined) {
							$myTeamIds[]	=	$joined->player_team_id;
						}
					}


					if($contestDetail->confirmed_winning=='' || $contestDetail->confirmed_winning=='0') {
						$winComfimed = 'no';
					}else{
						$winComfimed = $contestDetail->confirmed_winning;
					}

					$is_adjustable	=	$contestDetail->is_adjustable;
					$prizeMoney		=	$contestDetail->winning_amount;
					$totalTeams		=	$contestDetail->contest_size;
					$entryfee		=	$contestDetail->entry_fee;
					$multipleTeam	=	($contestDetail->multiple_team == 'yes') ? true : false;
					$max_team_user 	= 	$contestDetail->max_team_user;
					$usable_bonus_percentage	=	$contestDetail->usable_bonus_percentage;

					$joinedTeams	=	$this->PlayerTeamContests->find('all')
										->where(['match_id'=>$decoded['match_id'],'contest_id'=>$decoded['contest_id']])->count();
					$is_joined		=	!empty($myTeams) ? true : false;
				}
				$matchStatus = $this->getMatchStatus($decoded['series_id'],$decoded['match_id']);

				$dynamic_contest_message = '';
                if( $is_adjustable ){
                    $dynamic_contest_message = DYNAMIC_CONTEST_MESSAGE;
                }

				$data['match_status']		=	$matchStatus;
				$data['prize_money']		=	$prizeMoney;
				$data['confirm_winning']	=	$winComfimed;
				$data['total_teams']		=	$totalTeams;
				$data['entry_fee']			=	$entryfee;
				$data['invite_code']		=	!empty($inviteCode) ? $inviteCode->invite_code : '';
				$data['join_multiple_teams']=	$multipleTeam;
				$data['max_team_user']     	= 	$max_team_user;
				$data['usable_bonus_percentage']=	$usable_bonus_percentage;
				$data['total_winners']		=	$toalWinner;
				$data['teams_joined']		=	$joinedTeams;
				$data['is_joined']			=	$is_joined;
				$data['my_team_ids']		=	$myTeamIds;
				$data['joined_team_list']	=	$teamRankData;
				$data['breakup_detail']		=	$customPrice;
				$data['server_time']		=	$serverTime;
				$data['is_adjustable']		    	= $is_adjustable;
				$data['winning_amount_maximum'] 	= (string)$winning_amount_maximum;
				$data['dynamic_contest_message'] 	= $dynamic_contest_message;
				$data['breakup_detail_maximum']		= $customPricemain;
				$data['first_prize']        = (int)$first_prize;
				
				$data1	=	$data;
				$status	=	true;
			} else {
				$message	=	__("Match id and contest_id are empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}

		$myTeamsCount	=	$this->PlayerTeams->find()
			->where(['user_id'=>$decoded['user_id'],'match_id'=>$decoded['match_id'],'series_id'=>$decoded['series_id']])->count();


		$joinedContestCount	=	$this->PlayerTeamContests->find()
			->where(['match_id'=>$decoded['match_id'],'series_id'=>$decoded['series_id'],'user_id'=>$decoded['user_id']])
			->count();


		//$data1->my_team_count		=	$myTeamsCount;
		//$data1->my_contest_count	=	$joinedContestCount;


		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1,'my_team_count'=>$myTeamsCount,'my_contest_count'=>$joinedContestCount);
		echo json_encode(array('response' => $response_data));
		die;
	}

	public function teamScores() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Users');
		$this->loadModel('LiveScore');
		if(!empty($decoded)) {
			if(!empty($decoded['series_id']) && !empty($decoded['match_id']) && !empty($decoded['user_id']) && !empty($decoded['language']) ) {

				$authUser	=	$this->Users->find()
				->where(['id'=>$decoded['user_id'],'status'=>ACTIVE])
				->count();

				if($authUser) {
					$user	=	$this->LiveScore->find()
					->select(['id','total_inning_score','matchStatus','comment','teamType'])
					->where(['seriesId'=>$decoded['series_id'],'matchId'=>$decoded['match_id']])
					->group('teamId')
					->toArray();
					if(!empty($user)) {
						
						foreach($user as $team) {
							if($team->teamType=='localteam'){
								$data1->local_team_score	=	$team->total_inning_score;
							} else {
								$data1->vistor_team_score	=	$team->total_inning_score;
							}
							if($team->matchStatus=='Not Started'){
								$data1->match_started	=	false;
							} else {
								$data1->match_started	=	true;
							}
							$data1->comment	=	$team->comment;
						}
					} else {
						$message	=	__('Match not started yet.',true);
					}
					$data1	=	$data1;
					$status	=	true;
					$message=	$message;
				} else {
					$message	=	__('Invalid user id.',true);
				}
			} else {
				$message	=	__("user id, language are empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}

	public function playerTeamList() {
		error_reporting(0);
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('PlayerTeams');
		$this->loadModel('DreamTeams');
		$this->loadModel('SeriesSquad');
		$this->loadModel('SeriesPlayers');
		$this->loadModel('LiveScore');
		$this->loadModel('PointSystem');

		if(!empty($decoded)) {
			if(!empty($decoded['user_id']) && !empty($decoded['match_id']) && !empty($decoded['series_id'])) {

				$trump_mode = ( isset($decoded['trump_mode']) && $decoded['trump_mode'] ) ? 1 : 0;

				$filter	=	'';
				if(isset($decoded['team_no'])) {
					$filter	=	array('team_count'=>$decoded['team_no']);
				}
				$result	=	$this->PlayerTeams->find()
							->select(['id','captain','vice_captain','team_count','points','twelveth','replace_player_ids','trump_mode','substitute_status'])
							->where([$filter,'user_id'=>$decoded['user_id'],'match_id'=>$decoded['match_id'],'series_id'=>$decoded['series_id'],'trump_mode'=>$trump_mode ])
							->contain(['PlayerTeamDetails'=>['fields'=>['id','player_team_id','player_id'],'PlayerRecord'=>['fields'=>['id','image','player_id','player_name','playing_role','player_credit']] ] ,'TwelvethPlayerRecord'=>['fields'=>['id','image','player_id','player_name','playing_role','player_credit']] ])
							->order(['team_count'=>'ASC'])
							->toArray();
				
					
				$dreamPlayers	=	$this->DreamTeams->find('list', ['keyField'=>'player_id','valueField'=>'player_id'])->where([ 'series_id'=>$decoded['series_id'],'match_id'=>$decoded['match_id'] ])->toArray();
				
				$seriesMatch	=	$this->SeriesSquad->find()
				->select(['localteam_id','type','localteam_players','visitorteam_players','LocalMstTeams.id','LocalMstTeams.flag','LocalMstTeams.team_short_name','VisitorMstTeams.id','VisitorMstTeams.flag','VisitorMstTeams.team_short_name'])
				->where(['match_id'=>$decoded['match_id'],'series_id'=>$decoded['series_id']])
				->contain(['LocalMstTeams','VisitorMstTeams'])
				->first();
				//pr($seriesMatch);die;
				$localteam_id		=	0;
				$localteam_name		=	'';
				$visitorteam_name	=	'';
				
				$mType = '';
				$json_players_arry = [];
				if(!empty($seriesMatch)) {
					$localteam_id		=	$seriesMatch->localteam_id;
					$localteam_name		=	$seriesMatch->local_mst_team->team_short_name;
					$visitorteam_name	=	$seriesMatch->visitor_mst_team->team_short_name;
					$mType	=	$seriesMatch->type;

					
					if(!empty($seriesMatch->localteam_players)){
						$localteam_players = json_decode($seriesMatch->localteam_players,true);
						$json_players_arry = $localteam_players['players'];
					}
					if(!empty($seriesMatch->visitorteam_players)){
						$visitorteam_players = json_decode($seriesMatch->visitorteam_players,true);
						$json_players_arry = $json_players_arry + $visitorteam_players['players'];
						//array_merge($json_players_arry,$visitorteam_players['players']);
					}

				}

				//pr($json_players_arry);die;

				$teamData	=	$this->SeriesPlayers->find('list', ['keyField'=>'player_id','valueField'=>'team_id'])->where(['series_id'=>$decoded['series_id'],'team_id'=>$localteam_id])->toArray();
				
				$rePnt = [];
				if(($mType=='Test') || ($mType=='First-class')){
					$rePnt	=	$this->PointSystem->find('all')->select(['othersCaptain','othersViceCaptain'])->where(['matchType'=>'3'])->first();
				} elseif ($mType=='ODI') {
					$rePnt	=	$this->PointSystem->find('all')->select(['othersCaptain','othersViceCaptain'])->where(['matchType'=>'2'])->first();
				} elseif ($mType=='T20') {
					$rePnt	=	$this->PointSystem->find('all')->select(['othersCaptain','othersViceCaptain'])->where(['matchType'=>'1'])->first();
				} elseif ($mType=='T10') {
					$rePnt	=	$this->PointSystem->find('all',array('conditions'=>array('matchType'=>'4')))->select(['othersCaptain','othersViceCaptain'])->first();
				}
				
				//Get player points
				$query = $this->LiveScore->find();
				$playerPoints 	=   $query->find('list', [
					'keyField' => 'playerId',
					'valueField' => 'point'
				])
				->select([
					'playerId',
					'point' => $query->func()->sum('point')
				])
				->where(['seriesId'=>$decoded['series_id'],'matchId'=>$decoded['match_id']])
				->group('playerId')
				->toArray();

				if(!empty($result)) {
					$captain	=	$viceCaptain	=	'';
					foreach($result as $key=>$records) {

						$localteam_count	=	0;
						$visitorteam_count	=	0;

						$playerTeamId	=	$records->id;
						$captain		=	$records->captain;
						$viceCaptain	=	$records->vice_captain;
						$twelveth		=	$records->twelveth;
						$substitute_status		=	$records->substitute_status;
						$replace_player_ids	=	$records->replace_player_ids;
						$playerTeamNo	=	$records->team_count;
						$totalPoints	=	$records->points;
						$trump_mode		=	$records->trump_mode;
						$playerDetail	=	[];
						
						$totalBowler=	$totalBatsman	=	$totalWicketkeeper	=	$totalAllrounder	=	0;
						if(!empty($records->player_team_details)) {
							$playerTeamDetails	=	$records->player_team_details;
							foreach($playerTeamDetails as $teamKey => $teamValue) {
							
								// Players Detail
								//pr( $teamValue);die;
								$playerImage	=	'';
								if(!empty($teamValue->player_record->image) && file_exists(WWW_ROOT.'/uploads/player_image/'.$teamValue->player_record->image)) {
									$playerImage	=	SITE_URL.'uploads/player_image/'.$teamValue->player_record->image;
								}

								//$point = $this->getPlayerPoint($decoded['series_id'],$decoded['match_id'],$teamValue->player_record->player_id,$captain,$viceCaptain);
								$point = ( !empty($playerPoints[$teamValue->player_record->player_id]) ) ? $playerPoints[$teamValue->player_record->player_id] : 0;

								if(!empty($rePnt)){
									$captainPoint		=	$rePnt->othersCaptain;
									$viceCaptainPoint	=	$rePnt->othersViceCaptain;
									if($captain == $teamValue->player_record->player_id){
										$point	=	($point*$captainPoint);
									}
									if($viceCaptain == $teamValue->player_record->player_id){
										$point	=	($point*$viceCaptainPoint);
									}
								}


								$islocalTeam	=	(!empty($teamData[$teamValue->player_record->player_id])) ? true : false;

								if($islocalTeam){
									$localteam_count++;
								} else {
									$visitorteam_count++;
								}

								$playing_role 	= $teamValue->player_record->playing_role;
								$player_credit 	= $teamValue->player_record->player_credit;
								$player_name 	= $teamValue->player_record->player_name;

								$json_player_info = ( isset( $json_players_arry[$teamValue->player_record->player_id] )) ? $json_players_arry[$teamValue->player_record->player_id] : [];
						
								if(!empty($json_player_info)){
									$playing_role	=	$json_player_info['player_role'];
									$player_credit	=	$json_player_info['player_credit'];
									$player_name	=	$json_player_info['player_name'];
								}
								
								$playerDetail[$teamKey]['name']		=	$player_name;
								$playerDetail[$teamKey]['player_id']=	$teamValue->player_record->player_id;
								$playerDetail[$teamKey]['image']	=	$playerImage;
								$playerDetail[$teamKey]['role']		=	$playing_role;
								$playerDetail[$teamKey]['credits']	=	$player_credit;
								$playerDetail[$teamKey]['points']	=	$point;
								$playerDetail[$teamKey]['is_local_team']=	$islocalTeam;
								$playerDetail[$teamKey]['in_dream_team']=	!empty($dreamPlayers[$teamValue->player_record->player_id]) ? true : false;
								$playerDetail[$teamKey]['twelveth_player']	=	false;
								
								if(strpos($playing_role, 'Wicketkeeper') !== false) {
									$totalWicketkeeper	+=	1;
									unset($playerTeamDetails[$teamKey]);
								} else 
								if(strpos($playing_role, 'Bowler') !== false) {
									$totalBowler	+=	1;
									unset($playerTeamDetails[$teamKey]);
								} else
								if(stripos($playing_role, 'Batsman') !== false) {
									$totalBatsman	+=	1;
									unset($playerTeamDetails[$teamKey]);
								} else
								if(stripos($playing_role, 'Allrounder') !== false) {
									$totalAllrounder	+=	1;
									unset($playerTeamDetails[$teamKey]);
								}
							}
							$status	=	true;
						}
						
						// add twelth player in player detail array
						if(!empty($records->twelveth_player_record)  && !$substitute_status) {
							$tplayerDetail = [];
							$playerRecord	=	$records->twelveth_player_record;
							$substituteImage=	'';
							if(!empty($playerRecord) && file_exists(WWW_ROOT.'/uploads/player_image/'.$playerRecord->image)) {
								$substituteImage=	SITE_URL.'uploads/player_image/'.$playerRecord->image;
							}
							
							$point = ( !empty($playerPoints[$playerRecord->player_id]) ) ? $playerPoints[$playerRecord->player_id] : 0;
							$islocalTeam	=	(!empty($teamData[$playerRecord->player_id])) ? true : false;

							$playing_role = $playerRecord->playing_role;
							$player_credit = $playerRecord->player_credit;

							$json_player_info = ( isset( $json_players_arry[$playerRecord->player_id] )) ? $json_players_arry[$playerRecord->player_id] : [];
							if(!empty($json_player_info)){
								$playing_role	=	$json_player_info['player_role'];
								$player_credit	=	$json_player_info['player_credit'];
							}

							$tplayerDetail['name']		=	$playerRecord->player_name;
							$tplayerDetail['player_id']=	$playerRecord->player_id;
							$tplayerDetail['image']	=	$playerImage;
							$tplayerDetail['role']		=	$playing_role;
							$tplayerDetail['credits']	=	$player_credit;
							$tplayerDetail['points']	=	$point;
							$tplayerDetail['is_local_team']=	$islocalTeam;
							$tplayerDetail['in_dream_team']=	!empty($dreamPlayers[$playerRecord->player_id]) ? true : false;
							$tplayerDetail['twelveth_player']	=	true;

							$playerDetail[] = $tplayerDetail;
						}

						$data[$key]['teamid']				=	$playerTeamId;
						$data[$key]['team_number']			=	$playerTeamNo;
						$data[$key]['total_point']			=	$totalPoints;
						$data[$key]['captain_player_id']	=	$captain;
						$data[$key]['vice_captain_player_id']	=	$viceCaptain;
						$data[$key]['total_bowler']			=	$totalBowler;
						$data[$key]['total_batsman']		=	$totalBatsman;
						$data[$key]['total_wicketkeeper']	=	$totalWicketkeeper;
						$data[$key]['total_allrounder']		=	$totalAllrounder;
						$data[$key]['player_details']		=	$playerDetail;
						$data[$key]['substitute_detail']	=	$substituteDetail;
						$data[$key]['twelveth_player_id']	=	($substitute_status) ? '' : $twelveth;
						$data[$key]['replace_player_ids']	=	$replace_player_ids;
						$data[$key]['trump_mode']			=	$trump_mode;
						$data[$key]['team1_name']			=	$localteam_name;
						$data[$key]['team2_name']			=	$visitorteam_name;
						$data[$key]['team1_pcount']			=	$localteam_count;
						$data[$key]['team2_pcouunt']		=	$visitorteam_count;
					}
				}
				$data1	=	$data;
			} else {
				$message	=	__("user id, match id or series id are empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'tokenexpire'=>0,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}

	public function joinedMatchesResult() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('PlayerTeamContests');
		$this->loadModel('MatchContest');
		$this->loadModel('Users');
		
		// set server Time Start
		$serverTimeZone	=	date_default_timezone_get();
		$timeZone		=	new \DateTimeZone($serverTimeZone);
		$currentDatTime	=	date('Y-m-d H:i:s');
		$time			=	new \DateTime($currentDatTime, $timeZone);
		$serverTime		=	$time->format('Y-m-d H:i:s'); 
		// set server Time End
		
		if(!empty($decoded)) {
			$currentDate	=	date('Y-m-d');
			$oneMonthDate	=	date('Y-m-d',strtotime('+4 Days'));
			$currentTime	=	date('H:i', strtotime(MATCH_DURATION));

			if(!empty($decoded['user_id'])) {
				//$this->log("joinedMatchesResult : - ".print_r($decoded, true), 'debug');
				$result_type = (isset($decoded['result_type'])) ? $decoded['result_type'] : 'lastweek'; //all

				$authUser	=	$this->Users->find()->select(['id'])->where(['id'=>$decoded['user_id'],'status'=>ACTIVE])->first();
				if(!empty($authUser)) {


					$filter['user_id']				=	$decoded['user_id'];
					$filter['SeriesSquad.status']	=	ACTIVE;
					
					// Get contest wise counting
					$query = $this->PlayerTeamContests->find('list', ['keyField'=>'match_id','valueField'=>'count']);
					$query->select(['count' => $query->func()->count('id'), 'match_id']);
					$query->where([ 'user_id'=>$decoded['user_id'] ]);
					$teamsJoinedContestMatchWise	=	$query->group(['match_id'])->toArray();
					//pr($teamsJoinedContestMatchWise);die;
					
					$completeDate	=	date('Y-m-d',strtotime('-6 days'));
					if($result_type != 'all'){
						$filter['SeriesSquad.date >= ']	=	$completeDate;
					}

					$completeMatch	=	$this->PlayerTeamContests->find()
					->select(['PlayerTeamContests.id','SeriesSquad.id','SeriesSquad.match_id','SeriesSquad.series_id','SeriesSquad.date','SeriesSquad.time','SeriesSquad.localteam_id','SeriesSquad.localteam','SeriesSquad.visitorteam_id','SeriesSquad.visitorteam'])
					->where(['SeriesSquad.match_status'=>MATCH_FINISH,$filter])
					//->contain(['SeriesSquad'=>['Series','LocalMstTeams','VisitorMstTeams']])
					->contain(['SeriesSquad'=>['Series'=>['fields'=>['name','short_name']],'LocalMstTeams'=>['fields'=>['flag','team_short_name']],'VisitorMstTeams'=>['fields'=>['flag','team_short_name']]]])
					->group(['SeriesSquad.match_id','SeriesSquad.series_id'])
					->order(['date'=>'DESC','time'=>'DESC']);
					//$completeMatch->cache('match_result_'.$decoded['user_id']);
					$completeMatch->toArray();
					
					$resultCount = $completeMatch->count();
					$old_data  = false;
					if($resultCount < 1){
						$old_data  = true;
						unset($filter['SeriesSquad.date >= ']);
						$this->loadModel('PlayerTeamContestResults');
						$completeMatch	=	$this->PlayerTeamContestResults->find()
						->select(['PlayerTeamContestResults.id','SeriesSquad.id','SeriesSquad.match_id','SeriesSquad.series_id','SeriesSquad.date','SeriesSquad.time','SeriesSquad.localteam_id','SeriesSquad.localteam','SeriesSquad.visitorteam_id','SeriesSquad.visitorteam'])
						->where(['SeriesSquad.match_status'=>MATCH_FINISH,$filter])
						->contain(['SeriesSquad'=>['Series'=>['fields'=>['name','short_name']],'LocalMstTeams'=>['fields'=>['flag','team_short_name']],'VisitorMstTeams'=>['fields'=>['flag','team_short_name']]]])
						->group(['SeriesSquad.match_id','SeriesSquad.series_id'])
						->order(['date'=>'DESC','time'=>'DESC']);
						$completeMatch->toArray();
					}

					$finishData	=	[];
					if(!empty($completeMatch)) {
						foreach($completeMatch as $key => $value) {
							$live	=	$value->series_squad;

							$totalContest	=	(!empty($teamsJoinedContestMatchWise[$live->match_id])) ? $teamsJoinedContestMatchWise[$live->match_id] : 0;
							
							$filePath		=	WWW_ROOT.'uploads/team_flag/';
							$localTeamFlag	=	$visitorTeamFlag	=	'';
							if(!empty($live->local_mst_team) && file_exists($filePath.$live->local_mst_team->flag)) {
								$localTeamFlag	=	SITE_URL.'uploads/team_flag/'.$live->local_mst_team->flag;
							}
							if(!empty($live->visitor_mst_team) && file_exists($filePath.$live->visitor_mst_team->flag)) {
								$visitorTeamFlag=	SITE_URL.'uploads/team_flag/'.$live->visitor_mst_team->flag;
							}
							$seriesName	=	!empty($live->series->short_name) ? $live->series->short_name : str_replace("Cricket ","",$live->series->name);
							$finishData[$key]['series_id']			=	$live->series_id;
							$finishData[$key]['match_id']			=	$live->match_id;
							$finishData[$key]['series_name']		=	$seriesName;
							$finishData[$key]['local_team_id']		=	$live->localteam_id;
							$finishData[$key]['local_team_name']	=	!empty($live->local_mst_team->team_short_name) ? $live->local_mst_team->team_short_name : $live->localteam;
							$finishData[$key]['local_team_flag']	=	$localTeamFlag;
							$finishData[$key]['visitor_team_id']	=	$live->visitorteam_id;
							$finishData[$key]['visitor_team_name']	=	!empty($live->visitor_mst_team->team_short_name) ? $live->visitor_mst_team->team_short_name : $live->visitorteam;
							$finishData[$key]['visitor_team_flag']	=	$visitorTeamFlag;
							$finishData[$key]['star_date']			=	$this->finalDate($live->date);
							$finishData[$key]['star_time']			=	$live->time;
							$finishData[$key]['total_contest']		=	!empty($totalContest) ? $totalContest : 0;
							$finishData[$key]['server_time']		=	$serverTime;
							$finishData[$key]['old_data']			=	$old_data;
						}
					}

					$data1->completed_match	=	$finishData;
					$data1->server_time		=	$serverTime;
					
					$status	=	true;
				} else {
					$message	=	__('Invalid user id.',true);
				}
			} else {
				$message	=	__('Please check user id is blank.',true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'tokenexpire'=>0,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}

	
    public function joinedMatchesResultPrevious() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('PlayerTeamContestResults');
		$this->loadModel('MatchContest');
		$this->loadModel('Users');
		
		// set server Time Start
		$serverTimeZone	=	date_default_timezone_get();
		$timeZone		=	new \DateTimeZone($serverTimeZone);
		$currentDatTime	=	date('Y-m-d H:i:s');
		$time			=	new \DateTime($currentDatTime, $timeZone);
		$serverTime		=	$time->format('Y-m-d H:i:s'); 
		// set server Time End
		
		if(!empty($decoded)) {
			$currentDate	=	date('Y-m-d');
			$oneMonthDate	=	date('Y-m-d',strtotime('+4 Days'));
			$currentTime	=	date('H:i', strtotime(MATCH_DURATION));
	
			if(!empty($decoded['user_id'])) {
				//$this->log("joinedMatchesResult : - ".print_r($decoded, true), 'debug');
				$result_type = (isset($decoded['result_type'])) ? $decoded['result_type'] : 'lastweek'; //all
	
				$authUser	=	$this->Users->find()->select(['id'])->where(['id'=>$decoded['user_id'],'status'=>ACTIVE])->first();
				if(!empty($authUser)) {
	
	
					$filter['user_id']				=	$decoded['user_id'];
					$filter['SeriesSquad.status']	=	ACTIVE;
					
					// Get contest wise counting
					$query = $this->PlayerTeamContestResults->find('list', ['keyField'=>'match_id','valueField'=>'count']);
					$query->select(['count' => $query->func()->count('id'), 'match_id']);
					$query->where([ 'user_id'=>$decoded['user_id'] ]);
					$teamsJoinedContestMatchWise	=	$query->group(['match_id'])->toArray();
					//pr($teamsJoinedContestMatchWise);die;
					
					$completeDate	=	date('Y-m-d',strtotime('-3 days'));
					if($result_type != 'all'){
						$filter['SeriesSquad.date >= ']	=	$completeDate;
					}
					$completeMatch	=	$this->PlayerTeamContestResults->find()
					->select(['PlayerTeamContestResults.id','SeriesSquad.id','SeriesSquad.match_id','SeriesSquad.series_id','SeriesSquad.date','SeriesSquad.time','SeriesSquad.localteam_id','SeriesSquad.localteam','SeriesSquad.visitorteam_id','SeriesSquad.visitorteam'])
					->where(['SeriesSquad.match_status'=>MATCH_FINISH,$filter])
					//->contain(['SeriesSquad'=>['Series','LocalMstTeams','VisitorMstTeams']])
					->contain(['SeriesSquad'=>['Series'=>['fields'=>['name','short_name']],'LocalMstTeams'=>['fields'=>['flag','team_short_name']],'VisitorMstTeams'=>['fields'=>['flag','team_short_name']]]])
					->group(['SeriesSquad.match_id','SeriesSquad.series_id'])
					->order(['date'=>'DESC','time'=>'DESC']);
					
					//$completeMatch->cache('match_result_'.$decoded['user_id']);
					$completeMatch->toArray();
	
					$finishData	=	[];
					if(!empty($completeMatch)) {
						foreach($completeMatch as $key => $value) {
							$live	=	$value->series_squad;
	
							$totalContest	=	(!empty($teamsJoinedContestMatchWise[$live->match_id])) ? $teamsJoinedContestMatchWise[$live->match_id] : 0;
							
							$filePath		=	WWW_ROOT.'uploads/team_flag/';
							$localTeamFlag	=	$visitorTeamFlag	=	'';
							if(!empty($live->local_mst_team) && file_exists($filePath.$live->local_mst_team->flag)) {
								$localTeamFlag	=	SITE_URL.'uploads/team_flag/'.$live->local_mst_team->flag;
							}
							if(!empty($live->visitor_mst_team) && file_exists($filePath.$live->visitor_mst_team->flag)) {
								$visitorTeamFlag=	SITE_URL.'uploads/team_flag/'.$live->visitor_mst_team->flag;
							}
							$seriesName	=	!empty($live->series->short_name) ? $live->series->short_name : str_replace("Cricket ","",$live->series->name);
							$finishData[$key]['series_id']			=	$live->series_id;
							$finishData[$key]['match_id']			=	$live->match_id;
							$finishData[$key]['series_name']		=	$seriesName;
							$finishData[$key]['local_team_id']		=	$live->localteam_id;
							$finishData[$key]['local_team_name']	=	!empty($live->local_mst_team->team_short_name) ? $live->local_mst_team->team_short_name : $live->localteam;
							$finishData[$key]['local_team_flag']	=	$localTeamFlag;
							$finishData[$key]['visitor_team_id']	=	$live->visitorteam_id;
							$finishData[$key]['visitor_team_name']	=	!empty($live->visitor_mst_team->team_short_name) ? $live->visitor_mst_team->team_short_name : $live->visitorteam;
							$finishData[$key]['visitor_team_flag']	=	$visitorTeamFlag;
							$finishData[$key]['star_date']			=	$this->finalDate($live->date);
							$finishData[$key]['star_time']			=	$live->time;
							$finishData[$key]['total_contest']		=	!empty($totalContest) ? $totalContest : 0;
							$finishData[$key]['server_time']		=	$serverTime;
						}
					}
	
					$data1->completed_match	=	$finishData;
					$data1->server_time		=	$serverTime;
					
					$status	=	true;
				} else {
					$message	=	__('Invalid user id.',true);
				}
			} else {
				$message	=	__('Please check user id is blank.',true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'tokenexpire'=>0,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function joinedContestListResult() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('PlayerTeamContestResults');
		$this->loadModel('Users');
		//$this->loadModel('Contest');
		$this->loadModel('PlayerTeamResults');
		$this->loadModel('MatchContest');
		$this->loadModel('SeriesSquad');
		$this->loadModel('CustomBreakupmain');
	
		if(!empty($decoded)) {
			if(!empty($decoded['user_id']) && !empty($decoded['match_id']) && !empty($decoded['series_id'])) {
				$authUser	=	$this->Users->find()->select(['id'])->where(['id'=>$decoded['user_id'],'status'=>ACTIVE])->first();
	
				$trump_mode = ( isset($decoded['trump_mode']) ) ? $decoded['trump_mode'] : '';
	
				$trum_condition = [];
				if( $trump_mode !='' ){
					//$trum_condition = ['PlayerTeamResults.trump_mode'=> $trump_mode];
					$trum_condition['PlayerTeamResults.trump_mode'] = $trump_mode;
				}
				
				if(!empty($authUser)) {
					$seriesSquad	=	$this->SeriesSquad->find()->select(['id'])->where(['series_id'=>$decoded['series_id'],'match_id'=>$decoded['match_id'],'match_status'=>MATCH_NOTSTART])->first();
					
					if(!empty($seriesSquad)) {
						$joinedTeams=	$this->PlayerTeamContestResults->find()
										->select([
											'PlayerTeamContestResults.id','PlayerTeamContestResults.rank','PlayerTeamResults.points','PlayerTeamResults.trump_mode','Contest.id','Contest.contest_type','Contest.confirmed_winning','Contest.entry_fee','Contest.winning_amount','Contest.contest_size','Contest.category_id','Contest.multiple_team','Contest.usable_bonus_percentage','Contest.is_adjustable'
										])
										->where(['PlayerTeamContestResults.user_id'=>$decoded['user_id'],'PlayerTeamContestResults.match_id'=>$decoded['match_id'],'PlayerTeamContestResults.series_id'=>$decoded['series_id'],$trum_condition ])
										->group(['PlayerTeamContestResults.contest_id'])
										->contain(['PlayerTeamResults','Contest'=>['CustomBreakup'=>['fields'=>['contest_id','start','end','name','price']]]])
										->toArray();
					} else {
						$joinedTeams=	$this->PlayerTeamContestResults->find()
										->select([
											'PlayerTeamContestResults.id','PlayerTeamContestResults.rank','PlayerTeamContestResults.contest_id','PlayerTeamResults.points','PlayerTeamResults.trump_mode','Contest.id','Contest.contest_type','Contest.confirmed_winning','Contest.entry_fee','Contest.winning_amount','Contest.contest_size','Contest.category_id','Contest.multiple_team','Contest.usable_bonus_percentage','Contest.is_adjustable'
										])
										->where(['PlayerTeamContestResults.user_id'=>$decoded['user_id'],'PlayerTeamContestResults.match_id'=>$decoded['match_id'],'PlayerTeamContestResults.series_id'=>$decoded['series_id'],$trum_condition ])
										->order(['PlayerTeamResults.points'=>'DESC'])
										->contain(['PlayerTeamResults','Contest'=>['CustomBreakup']])
										->toArray();
						if(!empty($joinedTeams)) {
							foreach($joinedTeams as $teams) {
								$pointsData[$teams->contest_id][]	=	$teams->player_team_result->points;
							}
						}
						if(!empty($joinedTeams)) {
							foreach($joinedTeams as $key => $teams) {
								if($teams->player_team_result->points != $pointsData[$teams->contest_id][0]) {
									unset($joinedTeams[$key]);
								}
							}
						}
					}
					
					$contest		=	[];
					$upComingData	=	[];
					$myTeamRank 	=	[];
					$pointsData		=	[];
					
	
					// Get contest wise counting
					$query = $this->PlayerTeamContestResults->find('list', ['keyField'=>'contest_id','valueField'=>'count']);
					$query->select(['count' => $query->func()->count('id'), 'contest_id']);
					$query->where([ 'match_id'=>$decoded['match_id'] ]);
					$teamsJoinedContestWise	=	$query->group(['contest_id'])->toArray();
					
	
					// Get user joined team id
					$teamsJoined		=	$this->PlayerTeamContestResults->find()
					->select(['PlayerTeamContestResults.id','PlayerTeamContestResults.contest_id','PlayerTeamContestResults.player_team_result_id','PlayerTeamContestResults.winning_amount','PlayerTeamResults.id','PlayerTeamResults.team_count'])
					->where(['PlayerTeamContestResults.match_id'=>$decoded['match_id'],'PlayerTeamContestResults.user_id'=>$decoded['user_id']])
					->contain(['PlayerTeamResults'])
					->toArray();
					
				
					$myTeamIdsArr	=	[];
					$myTeamNoArr	=	[];
					$winningAmtArr	=	[];
					if(!empty($teamsJoined)){
						foreach($teamsJoined AS $joined){
							$myTeamIdsArr[$joined->contest_id][]	=	$joined->player_team_result_id;
							$myTeamNoArr[$joined->contest_id][]	=	!empty($joined->player_team_result) ? $joined->player_team_result->team_count : 0;
							$winningAmtArr[$joined->contest_id][]	=	!empty($joined->winning_amount) ? $joined->winning_amount : 0;
						}
					}
					//pr($myTeamIdsArr);
					//pr($myTeamNoArr);
					//pr($winningAmtArr);
					//die;
					
					if(!empty($joinedTeams)) {
						$contestKey	=	0;
						foreach($joinedTeams as $conKey => $contestValue) {
							// pr($contestValue->contest->contest_type);
							$inviteCode	=	$this->getInviteCode($decoded['match_id'],$contestValue->contest->id);
							$myTeamRank[] = $contestValue->rank;
							
							// price breakup for perticular contest
							$customBreakup	=	end($contestValue->contest->custom_breakup);
							if(!empty($customBreakup) &&!empty($customBreakup->end)) {
								$toalWinner	=	$customBreakup->end;
							} else {
								$toalWinner	=	!empty($customBreakup) ? $customBreakup->start : 0;
							}
							
							// find how other teams joined this contest
							//$joinedTeamCount=	$this->PlayerTeamContestResults->find()->where(['match_id'=>$decoded['match_id'],'contest_id'=>$contestValue->contest->id])->count();
							$joinedTeamCount	=	(!empty($teamsJoinedContestWise[$contestValue->contest->id])) ? $teamsJoinedContestWise[$contestValue->contest->id] : 0;
							
							
							/* $myTeamIds	=	[];
							$myTeamNo	=	[];
							$winningAmt	=	[];
	
							$playerContestFilter	=	[
								'PlayerTeamContestResults.match_id'	=>	$decoded['match_id'],
								'PlayerTeamContestResults.contest_id'	=>	$contestValue->contest->id,
								'PlayerTeamContestResults.user_id'	=>	$decoded['user_id']
							];
	
							$teamsJoined=	$this->PlayerTeamContestResults->find()
							->select(['PlayerTeamContestResults.id','PlayerTeamContestResults.player_team_result_id','PlayerTeamContestResults.winning_amount','PlayerTeamResults.id','PlayerTeamResults.team_count'])
							->where([$playerContestFilter])
							->contain(['PlayerTeamResults'])
							->toArray();
							if(!empty($teamsJoined)) {
								foreach($teamsJoined as $joined) {
									$myTeamIds[]	=	$joined->player_team_result_id;
									$myTeamNo[]		=	!empty($joined->player_team_result) ? $joined->player_team_result->team_count : 0;
									$winningAmt[]	=	!empty($joined->winning_amount) ? $joined->winning_amount : 0;
								}
							} */
	
							$myTeamIds	=	[];
							$myTeamNo	=	[];
							$winningAmt	=	[];
							if(!empty($myTeamIdsArr)){
								$myTeamIds	=	( !empty($myTeamIdsArr[$contestValue->contest->id]) ) ? $myTeamIdsArr[$contestValue->contest->id] : [];
							}
							if(!empty($myTeamNoArr)){
								$myTeamNo	=	( !empty($myTeamNoArr[$contestValue->contest->id]) ) ? $myTeamNoArr[$contestValue->contest->id] : [];
							}
							if(!empty($winningAmtArr)){
								$winningAmt	=	( !empty($winningAmtArr[$contestValue->contest->id]) ) ? $winningAmtArr[$contestValue->contest->id] : [];
							}
	
	
							$customPrice	=	[];
							$isWinner		=	false;
							$first_prize = '';
							if(!empty($contestValue->contest->custom_breakup)) {
								foreach($contestValue->contest->custom_breakup as $key=> $customBreakup) {
									// $isWinner		=	false;
									if(($contestValue->rank >= $customBreakup->start && $contestValue->rank <= $customBreakup->end) || $contestValue->rank == $customBreakup->end) {
										$isWinner	=	true;
									}
									if($customBreakup->start == $customBreakup->end) {
										$customPrice[$key]['rank']	=	'Rank '.$customBreakup->start;
									} else {
										$customPrice[$key]['rank']	=	$customBreakup->name;
									}
									// $customPrice[$key]['rank']	=	$customBreakup->name;
									$customPrice[$key]['price']	=	$customBreakup->price;
									if( $first_prize == '' ){
										$first_prize = $customBreakup->price;
									}
								}
							} else if(strpos($contestValue->contest->contest_type,'free') != false && $contestValue->rank == 1) {
								$isWinner	=	true;
							}
	
							$customPricemain	=	[];
							$winning_amount_maximum = 0;
							if ( $contestValue->contest->is_adjustable ) {
								$custom_breakupmain = $this->CustomBreakupmain->find()
								->where(['contest_id'=>$contestValue->contest->id, 'match_id'=>$decoded['match_id']])
								->toArray();
								if(!empty($custom_breakupmain)) {
									$isWinner    = false;
									foreach($custom_breakupmain as $key=> $customBreakup) {
	
										if (($contestValue->rank >= $customBreakup->start && $contestValue->rank <= $customBreakup->end) || $contestValue->rank == $customBreakup->end) {
											$isWinner = true;
										}
	
										if($customBreakup->start == $customBreakup->end) {
											$customPricemain[$key]['rank']	=	'Rank '.$customBreakup->start;
										} else {
											$customPricemain[$key]['rank']	=	$customBreakup->name;
										}
										$customPricemain[$key]['price']	=	$customBreakup->price;
										
										//Calculate Prize Pool
										$levelWinner = ( $customBreakup->end - ($customBreakup->start-1) );
										$levelPrize = ($levelWinner * $customBreakup->price);
										$winning_amount_maximum += $levelPrize;
	
									}
								}
							}
							
							if($contestValue->contest->confirmed_winning=='' || $contestValue->contest->confirmed_winning=='0'){
								$winComfimed = 'no';
							}else{
								$winComfimed = $contestValue->contest->confirmed_winning;
							}
	
							$dynamic_contest_message = '';
							if( $contestValue->contest->is_adjustable ){
								$dynamic_contest_message = DYNAMIC_CONTEST_MESSAGE;
							}
	
							$contest[$contestKey]['confirm_winning']=	$winComfimed;
							$contest[$contestKey]['entry_fee']		=	$contestValue->contest->entry_fee;
							$contest[$contestKey]['prize_money']	=	$contestValue->contest->winning_amount;
							$contest[$contestKey]['total_teams']	=	$contestValue->contest->contest_size;
							$contest[$contestKey]['category_id']	=	$contestValue->contest->category_id;
							$contest[$contestKey]['contest_id']		=	$contestValue->contest->id;
							$contest[$contestKey]['total_winners']	=	(int) $toalWinner;
							$contest[$contestKey]['teams_joined']	=	$joinedTeamCount;
							$contest[$contestKey]['is_joined']		=	!empty($myTeamIds) ? true : false;
							$contest[$contestKey]['multiple_team']	=	($contestValue->contest->multiple_team == 'yes') ? true : false;
							$contest[$contestKey]['max_team_user']  = 	$contestValue->contest->max_team_user;
							$contest[$contestKey]['usable_bonus_percentage']	=	$contestValue->contest->usable_bonus_percentage;
							$contest[$contestKey]['invite_code']	=	!empty($inviteCode)? $inviteCode->invite_code : '';
							$contest[$contestKey]['breakup_detail']	=	$customPrice;
							$contest[$contestKey]['my_team_ids']	=	$myTeamIds;
							$contest[$contestKey]['team_number']	=	$myTeamNo;
							$contest[$contestKey]['points_earned']	=	!empty($contestValue->player_team_result)? $contestValue->player_team_result->points : 0;
							$contest[$contestKey]['my_rank']		=	$contestValue->rank;
							$contest[$contestKey]['is_winner']		=	$isWinner;
							$contest[$contestKey]['winning_amount']	=	array_sum($winningAmt);
							$contest[$contestKey]['trump_mode']		=	!empty($contestValue->player_team_result)? $contestValue->player_team_result->trump_mode : 0;
							$contest[$contestKey]['winning_amount_maximum']  = (string)$winning_amount_maximum;
							$contest[$contestKey]['dynamic_contest_message']  = $dynamic_contest_message;
							$contest[$contestKey]['is_adjustable']	 = $contestValue->contest->is_adjustable;
							$contest[$contestKey]['breakup_detail_maximum']	=	$customPricemain;
							$contest[$contestKey]['first_prize']        = (int)$first_prize;
							$contestKey++;
						}
					} else {
						$currentDate	=	date('Y-m-d');
						$oneMonthDate	=	date('Y-m-d',strtotime('+4 Days'));
						$currentTime	=	date('H:i', strtotime('+30 min'));
						$upCommingMatch	=	$this->SeriesSquad->find()
						->select(['SeriesSquad.id','SeriesSquad.series_id','SeriesSquad.match_id','SeriesSquad.localteam_id','SeriesSquad.localteam','SeriesSquad.visitorteam_id','SeriesSquad.visitorteam','SeriesSquad.date','SeriesSquad.time','LocalMstTeams.id','LocalMstTeams.flag','LocalMstTeams.team_short_name','VisitorMstTeams.id','VisitorMstTeams.flag','VisitorMstTeams.team_short_name','Series.id','Series.name','Series.short_name'])
						->where(['OR'=>[['date'=>$currentDate,'time >= '=>$currentTime],['date > '=>$currentDate,'date <= '=>$oneMonthDate,'Series.status'=>ACTIVE,'SeriesSquad.status'=>ACTIVE]]])
						->contain(['Series','LocalMstTeams','VisitorMstTeams'])
						->order(['date','time'])
						->limit(3)
						->toArray();
						
						if(!empty($upCommingMatch)) {
							foreach($upCommingMatch as $key => $upComing) {
								$totalContest	=	$this->MatchContest->find()->where(['match_id'=>$upComing->id])->count();
								
								$filePath		=	WWW_ROOT.'uploads/team_flag/';
								$localTeamFlag	=	$visitorTeamFlag	=	'';
								if(!empty($upComing->local_mst_team->flag) && file_exists($filePath.$upComing->local_mst_team->flag)) {
									$localTeamFlag	=	SITE_URL.'uploads/team_flag/'.$upComing->local_mst_team->flag;
								}
								if(!empty($upComing->visitor_mst_team->flag) && file_exists($filePath.$upComing->visitor_mst_team->flag)) {
									$visitorTeamFlag=	SITE_URL.'uploads/team_flag/'.$upComing->visitor_mst_team->flag;
								}
								$seriesName	=	!empty($upComing->series->short_name) ? $upComing->series->short_name : str_replace("Cricket ","",$upComing->series->name);
								$upComingData[$key]['series_id']		=	$upComing->series_id;
								$upComingData[$key]['match_id']			=	$upComing->match_id;
								$upComingData[$key]['series_name']		=	$seriesName;
								$upComingData[$key]['local_team_id']	=	$upComing->localteam_id;
								$upComingData[$key]['local_team_name']	=	!empty($upComing->local_mst_team->team_short_name) ? $upComing->local_mst_team->team_short_name : $upComing->localteam;
								$upComingData[$key]['local_team_flag']	=	$localTeamFlag;
								$upComingData[$key]['visitor_team_id']	=	$upComing->visitorteam_id;
								$upComingData[$key]['visitor_team_name']=	!empty($upComing->visitor_mst_team->team_short_name) ? $upComing->visitor_mst_team->team_short_name : $upComing->visitorteam;;
								$upComingData[$key]['visitor_team_flag']=	$visitorTeamFlag;
								$upComingData[$key]['star_date']		=	$this->finalDate($upComing->date);
								$upComingData[$key]['star_time']		=	date('H:i',strtotime($upComing->time.'-30 min'));
								$upComingData[$key]['total_contest']	=	!empty($totalContest) ? $totalContest : 0;
							}
						}
					}
					// team count how team are created for perticular match.
					$myTeams	=	$this->PlayerTeamResults->find()
									->where(['user_id'=>$decoded['user_id'],'match_id'=>$decoded['match_id'],'series_id'=>$decoded['series_id']])->count();
					
					$status	=	true;
					$data1->joined_contest	=	$contest;
					$data1->upcoming_match	=	$upComingData;
					$data1->my_team_count	=	$myTeams;
					$data1->my_team_rank	=	$myTeamRank;
				} else {
					$message	=	__('Invalid user id.',true);
				}
			} else {
				$message	=	__("user id, match id or contest id are empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'tokenexpire'=>0,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function contestDetailResult() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Contest');
		$this->loadModel('PlayerTeamResults');
		$this->loadModel('PlayerTeamContestResults');
		$this->loadModel('CustomBreakupmain');
		
		// set server Time Start
		$serverTimeZone	=	date_default_timezone_get();
		$timeZone		=	new \DateTimeZone($serverTimeZone);
		$currentDatTime	=	date('Y-m-d H:i:s');
		$time			=	new \DateTime($currentDatTime, $timeZone);
		$serverTime		=	$time->format('Y-m-d H:i:s'); 
		// set server Time End
		
		if(!empty($decoded)) {
			if(!empty($decoded['match_id']) && !empty($decoded['contest_id']) && !empty($decoded['series_id'])) {
				$contestDetail	=	$this->Contest->find()->where(['id'=>$decoded['contest_id']])->contain(['CustomBreakup'])->first();
				$prizeMoney	=	$totalTeams	=	$teamsJoined	=	$toalWinner	=	$entryfee	=	0;
				$inviteCode	=	'';
				$teamData	=	$myTeamIds	=	$customPrice	=	[];
				if(!empty($contestDetail)) {
					$inviteCode	=	$this->getInviteCode($decoded['match_id'],$decoded['contest_id']);
					
					$customBreakup	=	end($contestDetail->custom_breakup);
					if(!empty($customBreakup) &&!empty($customBreakup->end)) {
						$toalWinner	=	$customBreakup->end;
					} else {
						$toalWinner	=	!empty($customBreakup) ? $customBreakup->start : 0;
					}
					
					$first_prize = '';
					if(!empty($contestDetail->custom_breakup)) {
						foreach($contestDetail->custom_breakup as $key=> $customBreakup) {
							if($customBreakup->start == $customBreakup->end) {
								$customPrice[$key]['rank']	=	'Rank '.$customBreakup->start;
							} else {
								$customPrice[$key]['rank']	=	$customBreakup->name;
							}
							$customPrice[$key]['price']	=	$customBreakup->price;
							if( $first_prize == '' ){
								$first_prize = $customBreakup->price;
							}
						}
					}
	
					$customPricemain	=	[];
					$winning_amount_maximum = 0;
					if ( $contestDetail->is_adjustable ) {
						$custom_breakupmain = $this->CustomBreakupmain->find()
						->where(['contest_id'=>$contestDetail->id, 'match_id'=>$decoded['match_id']])
						->toArray();
						if(!empty($custom_breakupmain)) {
							foreach($custom_breakupmain as $key=> $customBreakup) {
								if($customBreakup->start == $customBreakup->end) {
									$customPricemain[$key]['rank']	=	'Rank '.$customBreakup->start;
								} else {
									$customPricemain[$key]['rank']	=	$customBreakup->name;
								}
								$customPricemain[$key]['price']	=	$customBreakup->price;
								
								//Calculate Prize Pool
								$levelWinner = ( $customBreakup->end - ($customBreakup->start-1) );
								$levelPrize = ($levelWinner * $customBreakup->price);
								$winning_amount_maximum += $levelPrize;
	
							}
						}
					}
					
					$myTeams	=	$this->PlayerTeamContestResults->find()
										->where(['PlayerTeamContestResults.match_id'=>$decoded['match_id'],'PlayerTeamContestResults.contest_id'=>$decoded['contest_id'],'PlayerTeamContestResults.user_id'=>$decoded['user_id']])
										->contain(['PlayerTeamResults'=>['fields'=>['team_count','id','points','substitute_status'],'PlayerTeamDetailResults'],'Users'=>['fields'=>['team_name','id','image']]])
										->order(['PlayerTeamContestResults.user_id'])->toArray();
					$allTeams	=	$this->PlayerTeamContestResults->find()
										->where(['PlayerTeamContestResults.match_id'=>$decoded['match_id'],'PlayerTeamContestResults.contest_id'=>$decoded['contest_id'],'PlayerTeamContestResults.user_id !='=>$decoded['user_id']])
										->contain(['PlayerTeamResults'=>['fields'=>['team_count','id','points','substitute_status'],'PlayerTeamDetailResults'],'Users'=>['fields'=>['team_name','id','image']]])
										->order(['PlayerTeamContestResults.user_id'])->toArray();
					$mergedTeam	=	array_merge($myTeams,$allTeams);
					// contest Team 
					if(!empty($mergedTeam)) {
						$teamCount	=	0;
						foreach($mergedTeam as $userTeam) {
							$player_ids = array();
							$player_ids_array = $userTeam->player_team_result->player_team_detail_results;
							foreach ($player_ids_array as $row) {
								$player_ids[] = $row->player_id;
							}
							// $winningAmount	=	$this->getWinningAmount($decoded['series_id'],$decoded['match_id'],$userTeam->rank,$decoded['contest_id']);
							$winAmount		=	!empty($userTeam->winning_amount) ? $userTeam->winning_amount : 0;
							// if(!empty($winningAmount)) {
								// $winAmount	=	$winningAmount;
							// }
							if(!empty($userTeam->user)) {
								$teamData[$teamCount]['user_id']			=	$userTeam->user->id;
								$teamData[$teamCount]['team_name']			=	$userTeam->user->team_name;
								$teamData[$teamCount]['team_no']			=	!empty($userTeam->player_team_result) ? $userTeam->player_team_result->team_count : 0;
								$teamData[$teamCount]['rank']				=	$userTeam->rank;
								$teamData[$teamCount]['previous_rank']		=	$userTeam->previous_rank;
								$teamData[$teamCount]['point']				=	$userTeam->player_team_result->points;
								$teamData[$teamCount]['substitute_status']	=	$userTeam->player_team_result->substitute_status;
								$teamData[$teamCount]['winning_amount']		=	$winAmount;
								
								$teamCount++;
							}
						}
					}
					
					// re-arrange team array according team rank
					$ranArr	= $MyUser =	[];
					if(!empty($teamData)) {
						foreach($teamData as $key => $teamss) {
							if($teamss['user_id'] == $decoded['user_id']) {
								$MyUser[]	=	$teamss;
								unset($teamData[$key]);
							}
						}
					}
					array_values($teamData);
					if(!empty($teamData)) {
						foreach($teamData as $key => $teamss) {
							$ranArr[$key]	=	$teamss['rank'];
						}
					}
					array_multisort($ranArr, SORT_ASC, $teamData);
					$teamRankData	=	array_merge($MyUser,$teamData);
					
					// Teams that I have joined with current contest
					$teamsJoined	=	$this->PlayerTeamContestResults->find('all')
										->where(['match_id'=>$decoded['match_id'],'contest_id'=>$decoded['contest_id'],'user_id'=>$decoded['user_id']])->toArray();
					if(!empty($teamsJoined)) {
						foreach($teamsJoined as $joined) {
							$myTeamIds[]	=	$joined->player_team_result_id;
						}
					}
					if($contestDetail->confirmed_winning=='' || $contestDetail->confirmed_winning=='0') {
						$winComfimed = 'no';
					}else{
						$winComfimed = $contestDetail->confirmed_winning;
					}
	
					$is_adjustable	=	$contestDetail->is_adjustable;
					$prizeMoney		=	$contestDetail->winning_amount;
					$totalTeams		=	$contestDetail->contest_size;
					$entryfee		=	$contestDetail->entry_fee;
					$multipleTeam	=	($contestDetail->multiple_team == 'yes') ? true : false;
					$max_team_user 	= 	$contestDetail->max_team_user;
					$usable_bonus_percentage	=	$contestDetail->usable_bonus_percentage;
					$joinedTeams	=	$this->PlayerTeamContestResults->find('all')
										->where(['match_id'=>$decoded['match_id'],'contest_id'=>$decoded['contest_id']])->count();
					$is_joined		=	!empty($teamsJoined) ? true : false;
				}
				$matchStatus = $this->getMatchStatus($decoded['series_id'],$decoded['match_id']);
	
				$dynamic_contest_message = '';
				if( $is_adjustable ){
					$dynamic_contest_message = DYNAMIC_CONTEST_MESSAGE;
				}
	
				$data['match_status']		=	$matchStatus;
				$data['prize_money']		=	$prizeMoney;
				$data['confirm_winning']	=	$winComfimed;
				$data['total_teams']		=	$totalTeams;
				$data['entry_fee']			=	$entryfee;
				$data['invite_code']		=	!empty($inviteCode) ? $inviteCode->invite_code : '';
				$data['join_multiple_teams']=	$multipleTeam;
				$data['max_team_user']     	= 	$max_team_user;
				$data['usable_bonus_percentage']=	$usable_bonus_percentage;
				$data['total_winners']		=	$toalWinner;
				$data['teams_joined']		=	$joinedTeams;
				$data['is_joined']			=	$is_joined;
				$data['my_team_ids']		=	$myTeamIds;
				$data['joined_team_list']	=	$teamRankData;
				$data['breakup_detail']		=	$customPrice;
				$data['server_time']		=	$serverTime;
				$data['is_adjustable']		    	= $is_adjustable;
				$data['winning_amount_maximum'] 	= (string)$winning_amount_maximum;
				$data['dynamic_contest_message'] 	= $dynamic_contest_message;
				$data['breakup_detail_maximum']		= $customPricemain;
				$data['first_prize']        = (int)$first_prize;
				
				$data1	=	$data;
				$status	=	true;
			} else {
				$message	=	__("Match id and contest_id are empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
	
		$myTeamsCount	=	$this->PlayerTeamResults->find()
			->where(['user_id'=>$decoded['user_id'],'match_id'=>$decoded['match_id'],'series_id'=>$decoded['series_id']])->count();
	
	
		$joinedContestCount	=	$this->PlayerTeamContestResults->find()
			->where(['match_id'=>$decoded['match_id'],'series_id'=>$decoded['series_id'],'user_id'=>$decoded['user_id']])
			->count();
	
	
		//$data1->my_team_count		=	$myTeamsCount;
		//$data1->my_contest_count	=	$joinedContestCount;
	
	
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1,'my_team_count'=>$myTeamsCount,'my_contest_count'=>$joinedContestCount);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function teamScoresResult() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Users');
		$this->loadModel('LiveScore');
		if(!empty($decoded)) {
			if(!empty($decoded['series_id']) && !empty($decoded['match_id']) && !empty($decoded['user_id']) && !empty($decoded['language']) ) {
	
	
				/* if( $decoded['game_type']  == '' ){
	
				} else {
	
				} */
	
	
				$authUser	=	$this->Users->find()->where(['id'=>$decoded['user_id'],'status'=>ACTIVE])->first();
				if(!empty($authUser)) {
					$user	=	$this->LiveScore->find()->where(['seriesId'=>$decoded['series_id'],'matchId'=>$decoded['match_id']])->group('teamId')->toArray();
					if(!empty($user)) {
						
						foreach($user as $team) {
							if($team->teamType=='localteam'){
								$score='';
								$part[0] = $part[1] = $part[2] = $part[3] = '';
								$part = explode(" ",$team->total_inning_score);
								$count = count($part);
								if($count=='4'){
									$score = $part[0].'-'.$team->wickets.' '.$part[1].$part[2].$part[3];
								}
								//$data1->local_team_score	=	$team->total_inning_score.' / '.$team->wickets;
								//$data1->local_team_score	=	$score;
								$data1->local_team_score	=	$team->total_inning_score;
							} else {
								$score='';
								$part[0] = $part[1] = $part[2] = $part[3] = '';
								$part = explode(" ",$team->total_inning_score);
								$count = count($part);
								if($count=='4'){
									$score = $part[0].'-'.$team->wickets.' '.$part[1].$part[2].$part[3];
								}
								//$data1->vistor_team_score	=	$team->total_inning_score.' / '.$team->wickets;
								//$data1->vistor_team_score	=	$score;
								$data1->vistor_team_score	=	$team->total_inning_score;
							}
							if($team->matchStatus=='Not Started'){
								$data1->match_started	=	false;
							} else {
								$data1->match_started	=	true;
							}
							$data1->comment	=	$team->comment;
						}
					} else {
						$message	=	__('Match not started yet.',true);
					}
					$data1	=	$data1;
					$status	=	true;
					$message=	$message;
				} else {
					$message	=	__('Invalid user id.',true);
				}
	
	
	
	
	
			} else {
				$message	=	__("user id, language are empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function playerTeamListResult() {
		error_reporting(0);
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('PlayerTeamResults');
		$this->loadModel('DreamTeams');
		$this->loadModel('SeriesSquad');
		$this->loadModel('SeriesPlayers');
		$this->loadModel('LiveScore');
		$this->loadModel('PointSystem');
	
		if(!empty($decoded)) {
			
			if(!empty($decoded['user_id']) && !empty($decoded['match_id']) && !empty($decoded['series_id'])) {
	
				$trump_mode = ( isset($decoded['trump_mode']) && $decoded['trump_mode'] ) ? 1 : 0;
	
				$filter	=	'';
				if(isset($decoded['team_no'])) {
					$filter	=	array('team_count'=>$decoded['team_no']);
				}
				$result	=	$this->PlayerTeamResults->find()
							->select(['id','captain','vice_captain','team_count','points','twelveth','replace_player_ids','trump_mode','substitute_status'])
							->where([$filter,'user_id'=>$decoded['user_id'],'match_id'=>$decoded['match_id'],'series_id'=>$decoded['series_id'],'trump_mode'=>$trump_mode ])
							->contain(['PlayerTeamDetailResults'=>['fields'=>['id','player_team_result_id','player_id'],'PlayerRecord'=>['fields'=>['id','image','player_id','player_name','playing_role','player_credit']] ] ,'TwelvethPlayerRecord'=>['fields'=>['id','image','player_id','player_name','playing_role','player_credit']] ])
							->order(['team_count'=>'ASC'])
							->toArray();
				
					
				$dreamPlayers	=	$this->DreamTeams->find('list', ['keyField'=>'player_id','valueField'=>'player_id'])->where([ 'series_id'=>$decoded['series_id'],'match_id'=>$decoded['match_id'] ])->toArray();
				
				$seriesMatch	=	$this->SeriesSquad->find()
				->select(['localteam_id','type','localteam_players','visitorteam_players','LocalMstTeams.id','LocalMstTeams.flag','LocalMstTeams.team_short_name','VisitorMstTeams.id','VisitorMstTeams.flag','VisitorMstTeams.team_short_name'])
				->where(['match_id'=>$decoded['match_id'],'series_id'=>$decoded['series_id']])
				->contain(['LocalMstTeams','VisitorMstTeams'])
				->first();
				//pr($seriesMatch);die;
				$localteam_id		=	0;
				$localteam_name		=	'';
				$visitorteam_name	=	'';
				
				$mType = '';
				$json_players_arry = [];
				if(!empty($seriesMatch)) {
					$localteam_id		=	$seriesMatch->localteam_id;
					$localteam_name		=	$seriesMatch->local_mst_team->team_short_name;
					$visitorteam_name	=	$seriesMatch->visitor_mst_team->team_short_name;
					$mType	=	$seriesMatch->type;
	
					
					if(!empty($seriesMatch->localteam_players)){
						$localteam_players = json_decode($seriesMatch->localteam_players,true);
						$json_players_arry = $localteam_players['players'];
					}
					if(!empty($seriesMatch->visitorteam_players)){
						$visitorteam_players = json_decode($seriesMatch->visitorteam_players,true);
						$json_players_arry = $json_players_arry + $visitorteam_players['players'];
						//array_merge($json_players_arry,$visitorteam_players['players']);
					}
	
				}
	
				//pr($json_players_arry);die;
	
				$teamData	=	$this->SeriesPlayers->find('list', ['keyField'=>'player_id','valueField'=>'team_id'])->where(['series_id'=>$decoded['series_id'],'team_id'=>$localteam_id])->toArray();
				
				$rePnt = [];
				if(($mType=='Test') || ($mType=='First-class')){
					$rePnt	=	$this->PointSystem->find('all')->select(['othersCaptain','othersViceCaptain'])->where(['matchType'=>'3'])->first();
				} elseif ($mType=='ODI') {
					$rePnt	=	$this->PointSystem->find('all')->select(['othersCaptain','othersViceCaptain'])->where(['matchType'=>'2'])->first();
				} elseif ($mType=='T20') {
					$rePnt	=	$this->PointSystem->find('all')->select(['othersCaptain','othersViceCaptain'])->where(['matchType'=>'1'])->first();
				} elseif ($mType=='T10') {
					$rePnt	=	$this->PointSystem->find('all',array('conditions'=>array('matchType'=>'4')))->select(['othersCaptain','othersViceCaptain'])->first();
				}
				
				//Get player points
				$query = $this->LiveScore->find();
				$playerPoints 	=   $query->find('list', [
					'keyField' => 'playerId',
					'valueField' => 'point'
				])
				->select([
					'playerId',
					'point' => $query->func()->sum('point')
				])
				->where(['seriesId'=>$decoded['series_id'],'matchId'=>$decoded['match_id']])
				->group('playerId')
				->toArray();
	
				if(!empty($result)) {
					$captain	=	$viceCaptain	=	'';
					foreach($result as $key=>$records) {
	
						$localteam_count	=	0;
						$visitorteam_count	=	0;
	
						$playerTeamId	=	$records->id;
						$captain		=	$records->captain;
						$viceCaptain	=	$records->vice_captain;
						$twelveth		=	$records->twelveth;
						$substitute_status		=	$records->substitute_status;
						$replace_player_ids	=	$records->replace_player_ids;
						$playerTeamNo	=	$records->team_count;
						$totalPoints	=	$records->points;
						$trump_mode		=	$records->trump_mode;
						$playerDetail	=	[];
						
						$totalBowler=	$totalBatsman	=	$totalWicketkeeper	=	$totalAllrounder	=	0;
						if(!empty($records->player_team_detail_results)) {
							$playerTeamDetails	=	$records->player_team_detail_results;
							foreach($playerTeamDetails as $teamKey => $teamValue) {
							
								// Players Detail
								//pr( $teamValue);die;
								$playerImage	=	'';
								if(!empty($teamValue->player_record->image) && file_exists(WWW_ROOT.'/uploads/player_image/'.$teamValue->player_record->image)) {
									$playerImage	=	SITE_URL.'uploads/player_image/'.$teamValue->player_record->image;
								}
	
								//$point = $this->getPlayerPoint($decoded['series_id'],$decoded['match_id'],$teamValue->player_record->player_id,$captain,$viceCaptain);
								$point = ( !empty($playerPoints[$teamValue->player_record->player_id]) ) ? $playerPoints[$teamValue->player_record->player_id] : 0;
	
								if(!empty($rePnt)){
									$captainPoint		=	$rePnt->othersCaptain;
									$viceCaptainPoint	=	$rePnt->othersViceCaptain;
									if($captain == $teamValue->player_record->player_id){
										$point	=	($point*$captainPoint);
									}
									if($viceCaptain == $teamValue->player_record->player_id){
										$point	=	($point*$viceCaptainPoint);
									}
								}
	
	
								$islocalTeam	=	(!empty($teamData[$teamValue->player_record->player_id])) ? true : false;
	
								if($islocalTeam){
									$localteam_count++;
								} else {
									$visitorteam_count++;
								}
	
								$playing_role 	= $teamValue->player_record->playing_role;
								$player_credit 	= $teamValue->player_record->player_credit;
								$player_name 	= $teamValue->player_record->player_name;
	
								$json_player_info = ( isset( $json_players_arry[$teamValue->player_record->player_id] )) ? $json_players_arry[$teamValue->player_record->player_id] : [];
						
								if(!empty($json_player_info)){
									$playing_role	=	$json_player_info['player_role'];
									$player_credit	=	$json_player_info['player_credit'];
									$player_name	=	$json_player_info['player_name'];
								}
								
								$playerDetail[$teamKey]['name']		=	$player_name;
								$playerDetail[$teamKey]['player_id']=	$teamValue->player_record->player_id;
								$playerDetail[$teamKey]['image']	=	$playerImage;
								$playerDetail[$teamKey]['role']		=	$playing_role;
								$playerDetail[$teamKey]['credits']	=	$player_credit;
								$playerDetail[$teamKey]['points']	=	$point;
								$playerDetail[$teamKey]['is_local_team']=	$islocalTeam;
								$playerDetail[$teamKey]['in_dream_team']=	!empty($dreamPlayers[$teamValue->player_record->player_id]) ? true : false;
								$playerDetail[$teamKey]['twelveth_player']	=	false;
								
								if(strpos($playing_role, 'Wicketkeeper') !== false) {
									$totalWicketkeeper	+=	1;
									unset($playerTeamDetails[$teamKey]);
								} else 
								if(strpos($playing_role, 'Bowler') !== false) {
									$totalBowler	+=	1;
									unset($playerTeamDetails[$teamKey]);
								} else
								if(stripos($playing_role, 'Batsman') !== false) {
									$totalBatsman	+=	1;
									unset($playerTeamDetails[$teamKey]);
								} else
								if(stripos($playing_role, 'Allrounder') !== false) {
									$totalAllrounder	+=	1;
									unset($playerTeamDetails[$teamKey]);
								}
							}
							$status	=	true;
						}
						
						// add twelth player in player detail array
						if(!empty($records->twelveth_player_record)  && !$substitute_status) {
							$tplayerDetail = [];
							$playerRecord	=	$records->twelveth_player_record;
							$substituteImage=	'';
							if(!empty($playerRecord) && file_exists(WWW_ROOT.'/uploads/player_image/'.$playerRecord->image)) {
								$substituteImage=	SITE_URL.'uploads/player_image/'.$playerRecord->image;
							}
							
							$point = ( !empty($playerPoints[$playerRecord->player_id]) ) ? $playerPoints[$playerRecord->player_id] : 0;
							$islocalTeam	=	(!empty($teamData[$playerRecord->player_id])) ? true : false;
	
							$playing_role = $playerRecord->playing_role;
							$player_credit = $playerRecord->player_credit;
	
							$json_player_info = ( isset( $json_players_arry[$playerRecord->player_id] )) ? $json_players_arry[$playerRecord->player_id] : [];
							if(!empty($json_player_info)){
								$playing_role	=	$json_player_info['player_role'];
								$player_credit	=	$json_player_info['player_credit'];
							}
	
							$tplayerDetail['name']		=	$playerRecord->player_name;
							$tplayerDetail['player_id']=	$playerRecord->player_id;
							$tplayerDetail['image']	=	$playerImage;
							$tplayerDetail['role']		=	$playing_role;
							$tplayerDetail['credits']	=	$player_credit;
							$tplayerDetail['points']	=	$point;
							$tplayerDetail['is_local_team']=	$islocalTeam;
							$tplayerDetail['in_dream_team']=	!empty($dreamPlayers[$playerRecord->player_id]) ? true : false;
							$tplayerDetail['twelveth_player']	=	true;
	
							$playerDetail[] = $tplayerDetail;
						}
	
						$data[$key]['teamid']				=	$playerTeamId;
						$data[$key]['team_number']			=	$playerTeamNo;
						$data[$key]['total_point']			=	$totalPoints;
						$data[$key]['captain_player_id']	=	$captain;
						$data[$key]['vice_captain_player_id']	=	$viceCaptain;
						$data[$key]['total_bowler']			=	$totalBowler;
						$data[$key]['total_batsman']		=	$totalBatsman;
						$data[$key]['total_wicketkeeper']	=	$totalWicketkeeper;
						$data[$key]['total_allrounder']		=	$totalAllrounder;
						$data[$key]['player_details']		=	$playerDetail;
						$data[$key]['substitute_detail']	=	$substituteDetail;
						$data[$key]['twelveth_player_id']	=	($substitute_status) ? '' : $twelveth;
						$data[$key]['replace_player_ids']	=	$replace_player_ids;
						$data[$key]['trump_mode']			=	$trump_mode;
						$data[$key]['team1_name']			=	$localteam_name;
						$data[$key]['team2_name']			=	$visitorteam_name;
						$data[$key]['team1_pcount']			=	$localteam_count;
						$data[$key]['team2_pcouunt']		=	$visitorteam_count;
					}
				}
				$data1	=	$data;
			} else {
				$message	=	__("user id, match id or series id are empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'tokenexpire'=>0,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function avetarList() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('UserAvetars');
		if(!empty($decoded)) {
			if(!empty($decoded['user_id'])) {
				$avetars	=	$this->UserAvetars->find()->toArray();
				$folderPath	=	SITE_URL.'uploads/avetars/';
				$avetarArr	=	[];
				if(!empty($avetars)) {
					foreach($avetars as $key => $images) {
						$avetarArr[$key]['id']		=	$images->id;
						$avetarArr[$key]['image']	=	$folderPath.$images->avetars;
					}
				}
				$status	=	true;
				$data1	=	$avetarArr;
			} else {
				$message	=	__('Please check user id is blank.',true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function updateUserImage() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Users');
		$this->loadModel('UserAvetars');
		if(!empty($decoded)) {
			if(!empty($decoded['user_id']) && !empty($decoded['img_id'])) {
				$user	=	$this->Users->find()->where(['Users.status'=>ACTIVE,'id'=>$decoded['user_id']])->first();
				if(!empty($user)) {
					$image	=	$this->UserAvetars->find()->where(['status'=>ACTIVE,'id'=>$decoded['img_id']])->first();
					if(!empty($image)) {
						$user->image	=	$image->avetars;
						$this->Users->Save($user);
						$status	=	true;
						$data1	=	$user;
						$message	=	__('Avetar updated successfully.');

					} else {
						$message	=	__('image not exist on our database.',true);
					}
				} else {
					$message	=	__('Invalid user id.',true);
				}
			} else {
				$message	=	__('Please check user id or image id is blank.',true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}


	public function uploadUserImage()
    {
        $status     = false;
        $message    = null;
        $data       = [];
        $data1      = (object) array();
        $data_row   = $this->request->data['data'];
        $decoded    = json_decode($data_row, true);
        $imgDecoded = $this->request->data;
        $this->loadModel('Users');
        
        if (!empty($decoded)) {
            if (!empty($decoded['user_id']) && !empty($imgDecoded['user_image'])) {
                $user = $this->Users->find()->where(['Users.status' => ACTIVE, 'id' => $decoded['user_id']])->first();
                if (!empty($user)) {
                    $userPath = WWW_ROOT . 'uploads' . DS . 'users' . DS;
                    $userimage = $imgDecoded['user_image'];
                    if (!empty($userimage)) {
						$imgArr        = explode(".", $userimage['name']);
						$ext           = end($imgArr);
						$imageName     = 'user_' . time() . $decoded['user_id'] . '.' . $ext;
						$newImage      = $userPath . $imageName;
						if(move_uploaded_file($userimage['tmp_name'], $newImage)) {
							if (!empty($user) && !empty($user->image)) {
								unlink($userPath . $user->image);
							}
						} else {
							$message = __('User Image could not upload.', true);
						}
						
						$user->image = $imageName;
						$user->image_updated = 1;
						$this->Users->Save($user);
						$status  = true;
						$data1   = '';
						$message = __('User Image updated successfully.');
					} else {
						$message = __('User Image not found.', true);
					}
                } else {
                    $message = __('Invalid user id.', true);
                }
            } else {
                $message = __('Please check user id or image id is blank.', true);
            }
        } else {
            $message = __("You are not authenticated user.", true);
        }
        $response_data = array('status' => $status,'tokenexpire'=>0, 'message' => $message, 'data' => $data1);
        echo json_encode(array('response' => $response_data));
        die;
    }
	
	/*
	 * Function to generate unique team name
	 */
	public function createTeamName($userName = null) {
		$userName	=	explode('@',$userName);
		$name		=	str_replace(' ','',$userName[0]);
		$nameStr	=	substr($name,0,4);
		
		$string		=	'0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ9876543210';
		$strShuffled=	str_shuffle($string);
		$shuffleCode=	substr($strShuffled, 1, 6);
		$teamName	=	strtoupper($nameStr.$shuffleCode);
		return $teamName;
		exit;
	}

	public function seriesList() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('SeriesSquad');
		$this->loadModel('Series');
		$this->loadModel('Users');
		if(!empty($decoded)) {
			if(!empty($decoded['user_id']) && !empty($decoded['language']) ) {
				$authUser	=	$this->Users->find()->where(['id'=>$decoded['user_id'],'status'=>ACTIVE])->first();
				if(!empty($authUser)) {
					$seriesList	=	$this->upcomingSeriesListApp();
					if(!empty($seriesList)) {
						$flag	=	0;
						foreach($seriesList as $key => $list) {
							$data[$flag]['series_id']	=	$key;
							$data[$flag]['series_name']	=	$list;
							$flag++;
						}
					}
					$data1	=	$data;
					$status	=	true;
					$message	=	__('Series List.');
				} else {
					$message	=	__('Invalid user id.',true);
				}
			} else {
				$message	=	__("user id, language are empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}

	
	
	public function updateTransactions() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Users');
		$this->loadModel('Transactions');
		$this->loadModel('UserCouponCodes');
		//require_once(ROOT . DS . 'vendor' . DS  . 'Razorpay' . DS . 'Razorpay.php');

		if(!empty($decoded)) {

			//$this->log("Response payment request updateTransactions: - ".print_r($decoded, true), 'debug');

			$decoded['txn_date'] = str_replace(' +0000','',$decoded['txn_date']);
			if(!empty($decoded['user_id']) && !empty($decoded['gateway_name']) && !empty($decoded['order_id']) && !empty($decoded['txn_id']) && !empty($decoded['banktxn_id']) && !empty($decoded['txn_date']) && !empty($decoded['txn_amount']) && !empty($decoded['currency'])) {
				$authUser	=	$this->Users->find()->where(['id'=>$decoded['user_id'],'status'=>ACTIVE])->first();
				if(!empty($authUser)) {
					$setting	=	Configure::read('Admin.setting');
					$transationStatus	=	false;
					if($decoded['gateway_name'] == 'CASH_FREE') {
						$apiEndpoint	=	LIVE_APIENDPOINT;
						$command = "curl --request POST --url ".$apiEndpoint."api/v1/order/info/status --header 'cache-control: no-cache' --header 'content-type:application/x-www-form-urlencoded' --data 'appId=".LIVE_APPID."&secretKey=".LIVE_SECRETKEY."&orderId=".$decoded['order_id']."'";

						$response		=	shell_exec($command);
						$jsonResponse	=	json_decode($response);
						// pr($jsonResponse);die;
						
						if(isset($jsonResponse->txStatus)&& ($jsonResponse->txStatus == "SUCCESS" || $jsonResponse->txStatus == "FLAGGED" ) ) {
							$transationStatus	=	true;
						}
						
					}
					
					if($transationStatus == true) {
						
						$txnEntity	=	$this->Transactions->find()->where([ 'order_id'=>$decoded['order_id'],'added_type'=> CASH_DEPOSIT ])->first();
						if(!empty($txnEntity)) {

							$response_data	=	array('status'=>$status,'message'=>'Amount added successfully.','data'=>$data1);
							echo json_encode(array('response' => $response_data));
							die;
							
						} else {
							$txnEntity	=	$this->Transactions->newEntity();
							$txnEntity->user_id		=	$decoded['user_id'];
							$txnEntity->order_id	=	$decoded['order_id'];
							$txnEntity->txn_id		=	$decoded['txn_id'];
							$txnEntity->banktxn_id	=	$decoded['banktxn_id'];
							$txnEntity->txn_date	=	date('Y-m-d H:i:s',strtotime($decoded['txn_date']));
							$txnEntity->txn_amount	=	$decoded['txn_amount'];
							$txnEntity->currency	=	$decoded['currency'];
							$txnEntity->gateway_name=	$decoded['gateway_name'];
							$txnEntity->checksum	=	$decoded['checksum'];
							$txnEntity->local_txn_id=	'DD'.date('Ymd').time().$decoded['user_id'];
							$txnEntity->added_type	=	CASH_DEPOSIT; // Deposit Cash status
							$txnEntity->status		=	1;
						}

						if(isset($txnEntity) && !empty($txnEntity)) {
							if($trResult = $this->Transactions->save($txnEntity)) {
								$users	=	$this->Users->find()->where(['id'=>$decoded['user_id'],'Users.status'=>ACTIVE])->first();
								if(!empty($users)) {
									$uid     		=   $users->id;
									$deviceType     =   $users->device_type;
									$deviceToken    =   $users->device_id;
									$notiType       =   '3';

									if(!empty($decoded['coupon_id']) && !empty($decoded['discount_amount'])) {
										$this->loadModel('CouponCodes');
										$couponCode	=	$this->CouponCodes->find()->where(['id'=>$decoded['coupon_id']])->first();
										if(!empty($couponCode)) {
											if($decoded['txn_amount'] >= $couponCode->min_amount) {
												$appkiedCount	=	$this->UserCouponCodes->find()->where(['coupon_code_id'=>$decoded['coupon_id'],'user_id'=>$decoded['user_id'],'status'=>ACTIVE ])->order(['id'=>'DESC'])->count();
												if($appkiedCount <= $couponCode->per_user_limit) {
													$r = 0;
													if($couponCode->usage_limit != 0){
														$allAppkiedCount	=	$this->UserCouponCodes->find()->where(['coupon_code_id'=>$decoded['coupon_id'],'status'=>ACTIVE ])->order(['id'=>'DESC'])->count();
														if($allAppkiedCount > $couponCode->usage_limit) {
															$r = 1;
														}
													}
													if($r == 0){
														if($couponCode->max_cashback_percent > 0) {
															$discountPercent	=	$couponCode->max_cashback_percent;
															$discountAmount	=	($discountPercent / 100) * $decoded['txn_amount'];
														} else {
															$discountAmount	=	$couponCode->max_cashback_amount;
														}
														$discountAmount	=	str_replace(',','',number_format($discountAmount,2));
														$decoded['discount_amount']	=	str_replace(',','',number_format($decoded['discount_amount'],2));
														if( $discountAmount > 0 ) {
															$couponCode	=	$this->UserCouponCodes->find()->where(['coupon_code_id'=>$decoded['coupon_id']])->order(['id'=>'DESC'])->first();
															$couponCode->status	=	ACTIVE;
															$this->UserCouponCodes->save($couponCode);
															
															$users->bonus_amount	+=	$discountAmount;
															
															$txnId	=	'CB'.date('Ymd').time().$users->id;
															$this->saveTransaction($users->id,$txnId,COUPON_BONUS,$discountAmount);
														}
													}
												}
											}
										}
									}

									if(isset($decoded['txn_amount']) && $decoded['txn_amount'] >= 1000){

										$cashbackamount = 25;
										if($decoded['txn_amount'] >= 1000 && $decoded['txn_amount'] <= 1999){
											$cashbackamount = 25;
										} else if ($decoded['txn_amount'] >= 2000 && $decoded['txn_amount'] <= 4999){
											$cashbackamount = 50;
										} else if ($decoded['txn_amount'] >= 5000 && $decoded['txn_amount'] <= 9999){
											$cashbackamount = 100;
										} else if ($decoded['txn_amount'] >= 10000 && $decoded['txn_amount'] <= 49999){
											$cashbackamount = 250;
										} else if ($decoded['txn_amount'] >= 50000){
											$cashbackamount = 1000;
										} else {
											$cashbackamount = 25;
										}

										$users->cash_balance	+=	$cashbackamount;				
										$txnId	=	'CB'.date('Ymd').time().$users->id;
										$this->saveTransaction($users->id,$txnId,COUPON_BONUS,$cashbackamount);
									}
									
									$users->cash_balance	+=	$decoded['txn_amount'];
									if($this->Users->save($users)) {

										//Calculate user total deposit and update flag
										$query = 	$this->Transactions->find(); 
										$totalDeposit = $query->select(['sum' => $query->func()->sum('Transactions.txn_amount')])
											->where([ 'Transactions.user_id'=>$users->id, 'Transactions.added_type'=> CASH_DEPOSIT ])
											->first();

										$totalDepositAmount =  0;
										if( !empty($totalDeposit) ){
											$totalDepositAmount =  $totalDeposit->sum;
										}


										//Give Referel Amount
										// get bonus to refferal user
										if($txnEntity->user_id){
											$this->loadModel('ReferalCodeDetails');
											$referralAmount	=	Configure::read('Admin.setting.referral_bouns_amount_referral');
											$min_deposit_for_referral	=	Configure::read('Admin.setting.min_deposit_for_referral');
											$refered	=	$this->ReferalCodeDetails->find()->where(['ReferalCodeDetails.user_id'=>$users->id, 'ReferalCodeDetails.status'=>0])->first();
											if(!empty($refered)) {
												$referedByUser	=	$this->Users->find()->where(['id'=>$refered->refered_by,'status'=>ACTIVE])->first();
												if( !empty($referedByUser) && $totalDepositAmount >= $min_deposit_for_referral ) {

													$referedByUser->bonus_amount	=	$referedByUser->bonus_amount + $referralAmount;

													if($this->Users->save($referedByUser)) {
														$refered->refered_by_amount	=	$referralAmount;
														$refered->status			=	1;
														$this->ReferalCodeDetails->save($refered);
														$transactionId1	=	'CB'.date('dmY').time().$referedByUser->id;
														$this->saveTransaction($referedByUser->id,$transactionId1,FRIEND_USED_INVITE,$referralAmount);
													}

													$user_id     	=   $referedByUser->id;
													$deviceType     =   $referedByUser->device_type;
													$deviceToken    =   $referedByUser->device_id;
													$notiType       =   '3';
													
													$title = 'Got Bonus';
													$notification = 'Your got bonus for using invite code.';
													if(($deviceType=='Android') && ($deviceToken!='')){
														$this->sendNotificationFCM($user_id,$notiType,$deviceToken,$title,$notification,'');
													} 
													if(($deviceType=='iphone') && ($deviceToken!='') && ($deviceToken!='device_id')){
														$this->sendNotificationAPNS($user_id,$notiType,$deviceToken,$title,$notification,'');
													}
												}
											}
										}



										/* if(!empty($decoded['coupon_id']) && !empty($decoded['discount_amount'])) {
											$txnId	=	'CB'.date('Ymd').time().$users->id;
											$this->saveTransaction($users->id,$txnId,COUPON_BONUS,$decoded['discount_amount']);
										} */
									}

									$title = 'Transaction';
									$notification = 'Your transaction is successful.';
									if(($deviceType=='Android') && ($deviceToken!='')){
										$this->sendNotificationFCM($uid,$notiType,$deviceToken,$title,$notification,'');
									} 
									if(($deviceType=='iphone') && ($deviceToken!='') && ($deviceToken!='device_id')){
										$this->sendNotificationAPNS($uid,$notiType,$deviceToken,$title,$notification,'');
									}
								}
								
								/* $apiEndpoint	=	LIVE_APIENDPOINT;
								$command = "curl --request POST --url ".$apiEndpoint."/api/v1/order/info/status --header 'cache-control: no-cache' --header 'content-type:application/x-www-form-urlencoded' --data 'appId=".LIVE_APPID."&secretKey=".LIVE_SECRETKEY."&orderId=".$decoded['order_id']."'";

								$response		=	shell_exec($command);
								$jsonResponse	=	json_decode($response);
								
								if(isset($jsonResponse->txStatus) && $jsonResponse->txStatus == "SUCCESS") {
									$trResult->status	=	1;
									$this->Transactions->save($trResult);
								}
								 */
								$status	=	true;
								$message=	__('Amount added successfully',true);
							}
						} else {
							$message=	__('Transaction Failed.',true);
						}
					} else {
						$message=	__('Could not add amount in your wallet.',true);
					}
				} else {
					$message	=	__('Invalid user id.',true);
				}
			} else {
				$message	=	__('Please check all details are correct or not.',true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function userAccountDatail() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Users');
		if(!empty($decoded)) {
			if(!empty($decoded['user_id'])) {
				$users	=	$this->Users->find()->where(['Users.id'=>$decoded['user_id'],'Users.status'=>ACTIVE])->contain(['BankDetails'])->first();
				if(!empty($users)) {
					$totalBalance	=	$cashBalance	=	$winngsAmount	=	$bonus	=	0;
					if(!empty($users)) {
						$cashBalance	=	$users->cash_balance;
						$winngsAmount	=	$users->winning_balance;
						$bonus			=	$users->bonus_amount;
					}
					$accountVerify	=	false;
					//if(!empty($users->bank_detail) && $users->bank_detail->is_verified == true) {
					if(!empty($users->bank_detail) && $users->bank_detail->is_verified == 1) {
						$accountVerify	=	true;
					}
					
					$data1->deposit_amount	=	$cashBalance;
					$data1->winngs_amount	=	$winngsAmount;
					$data1->bonus			=	$bonus;
					$data1->total_balance	=	$cashBalance + $winngsAmount + $bonus;
					$data1->account_verified=	$accountVerify;

					$random_val = rand();
					$secure_id 	= $random_val.'###'.$decoded['user_id'].'##'.APP_SECURE_KEY;   
					$encrypted 	= $this->General->encrypt_decrypt('encrypt', $secure_id);
					$encrypted 	= urlencode($encrypted);
					$notify_url = SITE_URL.'crones/cftransaction/'.$encrypted;
					$data1->notify_url =	$notify_url;

					$status		=	true;
				} else {
					$message	=	__('Invalid user id.',true);
				}
			} else {
				$message	=	__('Please check all details are correct or not.',true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function transationHistoryNew() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Users');
		$this->loadModel('Transactions');
		if(!empty($decoded)) {
			if(!empty($decoded['user_id'])) {
				
				$tran_type = (isset($decoded['type'])) ? $decoded['type'] : '';
				if( $tran_type != '' ){
					if( $tran_type=='deposit' ) {
						$conditions = ['user_id'=>$decoded['user_id'],'added_type'=>1];
					} elseif( $tran_type=='winnings' ) {
						$conditions = ['user_id'=>$decoded['user_id'],'added_type'=>4];
					} elseif( $tran_type=='withdraw' ) {
						$conditions = ['user_id'=>$decoded['user_id'],'added_type IN '=>[11,12,13]];
					} else { //other
						$conditions = ['user_id'=>$decoded['user_id'],'added_type NOT IN '=>[1,4,11,12,13]];
					}
				} else {
					$conditions = ['user_id'=>$decoded['user_id']];
				}

				$authUser	=	$this->Users->find()->select(['id'])->where(['id'=>$decoded['user_id'],'status'=>ACTIVE])->first();
				if(!empty($authUser)) {

					$query 	=	$this->Transactions->find('list', ['keyField'=>'id','valueField'=>'txn_date_formatted']);
					$dateformat = $query->func()->date_format([
						'txn_date' => 'identifier',
						"'%Y-%m-%d'" => 'literal'
					]);
					
					$dateArr	=	$query
					->select(['id','txn_date_formatted'=> $dateformat])
					->where($conditions)
					->order(['txn_date'=>'DESC'])
					->group(['txn_date_formatted'])
					->toArray();
					

					$txnsArr	=	[];
					if(!empty($dateArr)) {
						$flag	=	0;
						foreach($dateArr as $dates) {

							$startDate	=	$dates.' 00:00:00';
							$endDate	=	$dates.' 23:59:59';

							if( $tran_type != '' ){
								if( $tran_type=='deposit' ) {
									$conditions = ['user_id'=>$decoded['user_id'],'txn_date >='=>$startDate,'txn_date <='=>$endDate,'added_type'=>1];
								} elseif( $tran_type=='winnings' ) {
									$conditions = ['user_id'=>$decoded['user_id'],'txn_date >='=>$startDate,'txn_date <='=>$endDate,'added_type'=>4];
								} elseif( $tran_type=='withdraw' ) {
									$conditions = ['user_id'=>$decoded['user_id'],'txn_date >='=>$startDate,'txn_date <='=>$endDate,'added_type IN '=>[11,12,13]];
								} else { //other
									$conditions = ['user_id'=>$decoded['user_id'],'txn_date >='=>$startDate,'txn_date <='=>$endDate,'added_type NOT IN '=>[1,4,11,12,13]];
								}
							} else {
								$conditions = ['user_id'=>$decoded['user_id'],'txn_date >='=>$startDate,'txn_date <='=>$endDate];
							}

							$txnHistory	=	$this->Transactions->find()
											->select(['Transactions.id','added_type','txn_amount','added_type','local_txn_id','txn_date','Users.team_name'])
											->where($conditions)
											->order(['txn_date'=>'DESC'])
											->contain(['Users'])->toArray();

							if(!empty($txnHistory)) {
								foreach($txnHistory as $key => $txns) {
									$txnsArr[$flag]['date']			=	$dates;
									$txnsArr[$flag]['info'][$key]	=	[
										'amount'		=>	($txns->added_type == JOIN_CONTEST) ? '- '.$txns->txn_amount : '+ '.$txns->txn_amount,
										'txn_type'		=>	Configure::read('TRANSACTION_TYPE.'.$txns->added_type),
										'transaction_id'=>	$txns->local_txn_id,
										'txn_date'		=>	date('d F,h:i:s a',strtotime($txns->txn_date)),
										'team_name'		=>	$txns->user->team_name,
									];
								}
								$flag++;
							}
						}
					}
					$data1	=	$txnsArr;
					$status	=	true;
					$message=	__('Transactions History',true);
				} else {
					$message	=	__('Invalid user id.',true);
				}
			} else {
				$message	=	__('Please check all details are correct or not.',true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function getSeriesPlayerList() {
		header('Content-Type: application/json');
		$totalTeam  =   $percent  =  '0'; 
		$status		=	false;
		$rslt		=	false;
		$message	=	NULL;
		$data		=	array();
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('LiveScore');
		$this->loadModel('PlayerTeams');
		$this->loadModel('PlayerTeamDetails');
		$this->loadModel('PlayerTeamContests');
		$this->loadModel('PointsBreakup');
		$this->loadModel('DreamTeams');
		$this->loadModel('Users');
		if($decoded) {
			if(!empty($decoded['user_id']) && !empty($decoded['language']) && !empty($decoded['series_id']) && !empty($decoded['match_id']) ) {
				$authUser	=	$this->Users->find()->where(['id'=>$decoded['user_id'],'status'=>ACTIVE])->first();
				if(!empty($authUser)) {
					$user_id	   =  $decoded['user_id'];
					$series_id     =  $decoded['series_id'];
					$match_id	   =  $decoded['match_id'];
					$contest_id	   =  isset($decoded['contest_id']) ? $decoded['contest_id'] : '';
					$totalTeam	   =  $this->PlayerTeams->find()->where(['series_id'=>$series_id,'match_id'=>$match_id])->count();
					if(isset($decoded['is_player_state'])) {
						$query		=	$this->LiveScore->find();
						$liveScore	=	$query->where(['seriesId'=>$series_id,'matchId'=>$match_id])->select(['playerId','point'=>$query->func()->sum('point')])->group(['playerId'])->toArray();
					} else {
						$liveScore       =	$this->LiveScore->find()->where(['seriesId'=>$series_id,'matchId'=>$match_id])->toArray();
					}
					
					// pr($liveScore);die;
					if(!empty($liveScore)) {
						foreach($liveScore as $row) {
							//pr($row);
							$playerCount	=	$this->PlayerTeamDetails->find()->contain(['PlayerTeams'])->where(['PlayerTeams.series_id'=>$series_id,'PlayerTeams.match_id'=>$match_id,'player_id'=>$row->playerId])->count();
							
							$joinedContest	=	$this->PlayerTeamContests->find('list', ['keyField'=>'id','valueField'=>'contest_id'])->where(['match_id'=>$match_id,'series_id'=>$series_id])->group(['contest_id'])->toArray();
							if(!empty($contest_id)) {
								$contestId	=	explode(',',$contest_id);
							} else {
								$contestId	=	$joinedContest;
							}
							
							if(!empty($contestId)) {
								$isInContest	=	$this->PlayerTeamDetails->find()->contain(['PlayerTeams'=>[
									'PlayerTeamContests'=>function($q) use ($contestId) {
										return $q->where(['PlayerTeamContests.contest_id IN'=>$contestId]);
									}
								]])->where(['player_id'=>$row->playerId,'PlayerTeams.series_id'=>$series_id,'PlayerTeams.match_id'=>$match_id,'PlayerTeams.user_id'=>$user_id])->toArray();
								$teamNo	=	[];
								if(!empty($isInContest)){
									foreach($isInContest as $key => $rows) {
										if(!empty($rows['player_team']['player_team_contests'])){
											$teamNo[]	=	$rows->player_team->team_count;
										}
									}
								}
							}
							if($totalTeam!='0'){
								$percent = (($playerCount/$totalTeam)*100);
								$percent = round($percent,2).'%';
							}
							$val = $this->getPlayerImage($row->playerId,$series_id);

							$playerRecord = NULL;
							if(isset($decoded['is_player_state'])) {
								$query		=  $this->PointsBreakup->find();
								$playerBrackup	=  $query
													->where(['series_id'=>$series_id,'match_id'=>$match_id,'player_id'=>$row->playerId])
													->select([
														'in_starting'=>$query->func()->sum('in_starting'),
														'in_starting_point'=>$query->func()->sum('in_starting_point'),
														'runs'=>$query->func()->sum('runs'),
														'runs_point'=>$query->func()->sum('runs_point'),
														'fours'=>$query->func()->sum('fours'),
														'fours_point'=>$query->func()->sum('fours_point'),
														'sixes'=>$query->func()->sum('sixes'),
														'sixes_point'=>$query->func()->sum('sixes_point'),
														'strike_rate'=>$query->func()->sum('strike_rate'),
														'strike_rate_point'=>$query->func()->sum('strike_rate_point'),
														'century_halfCentury'=>$query->func()->sum('century_halfCentury'),
														'century_halfCentury_point'=>$query->func()->sum('century_halfCentury_point'),
														'duck_out'=>$query->func()->sum('duck_out'),
														'duck_out_point'=>$query->func()->sum('duck_out_point'),
														'wickets'=>$query->func()->sum('wickets'),
														'wickets_point'=>$query->func()->sum('wickets_point'),
														'maiden_over'=>$query->func()->sum('maiden_over'),
														'maiden_over_point'=>$query->func()->sum('maiden_over_point'),
														'economy_rate'=>$query->func()->sum('economy_rate'),
														'economy_rate_point'=>$query->func()->sum('economy_rate_point'),
														'bonus'=>$query->func()->sum('bonus'),
														'bonus_point'=>$query->func()->sum('bonus_point'),
														'catch'=>$query->func()->sum('catch'),
														'catch_point'=>$query->func()->sum('catch_point'),
														'run_outStumping'=>$query->func()->sum('run_outStumping'),
														'run_outStumping_point'=>$query->func()->sum('run_outStumping_point'),
														'run_out'=>$query->func()->sum('run_out'),
														'run_out_point'=>$query->func()->sum('run_out_point'),
														'runout_thrower'=>$query->func()->sum('runout_thrower'),
														'runout_thrower_point'=>$query->func()->sum('runout_thrower_point'),
														'runout_catcher'=>$query->func()->sum('runout_catcher'),
														'runout_catcher_point'=>$query->func()->sum('runout_catcher_point'),
														'total_point'=>$query->func()->sum('total_point'),
													])
													->toArray();
							} else {
								$playerBrackup	=  $this->PointsBreakup->find()->where(['series_id'=>$series_id,'match_id'=>$match_id,'player_id'=>$row->playerId,'inning_number'=>$row->inning_number])->toArray();
							}
							
							if(!empty($playerBrackup)) {
								foreach ($playerBrackup as $value) {
									$playerRecord['starting11']=array(
										'actual'	=>	$value->in_starting,
										'points'	=> 	$value->in_starting_point
									);
									$playerRecord['runs']=array(
										'actual'	=>	$value->runs,
										'points'	=> 	$value->runs_point
									);
									$playerRecord['fours']=array(
										'actual'	=>	$value->fours,
										'points'	=> 	$value->fours_point
									);
									$playerRecord['sixes']=array(
										'actual'	=>	$value->sixes,
										'points'	=> 	$value->sixes_point
									);
									$playerRecord['strike_rate']=array(
										'actual'	=>	$value->strike_rate,
										'points'	=> 	$value->strike_rate_point
									);
									$playerRecord['half_century']=array(
										'actual'	=>	$value->century_halfCentury,
										'points'	=> 	$value->century_halfCentury_point
									);
									$playerRecord['duck']=array(
										'actual'	=>	$value->duck_out,
										'points'	=> 	$value->duck_out_point
									);
									$playerRecord['wickets']=array(
										'actual'	=>	$value->wickets,
										'points'	=> 	$value->wickets_point
									);
									$playerRecord['maiden_over']=array(
										'actual'	=>	$value->maiden_over,
										'points'	=> 	$value->maiden_over_point
									);
									$playerRecord['eco_rate']=array(
										'actual'	=>	$value->economy_rate,
										'points'	=> 	$value->economy_rate_point
									);
									$playerRecord['bonus']=array(
										'actual'	=>	$value->bonus,
										'points'	=> 	$value->bonus_point
									);
									$playerRecord['catch']=array(
										'actual'	=>	$value->catch,
										'points'	=> 	$value->catch_point
									);
									
									$actual		=	((int) $value->run_outStumping) + ((int) $value->run_out);
									$pointsRun	=	((int) $value->run_outStumping_point) + ((int) $value->run_out_point);
									
									$playerRecord['run_outStumping']=array(
										'actual'	=>	(string) $actual,
										'points'	=> 	(string) $pointsRun
									);

									$playerRecord['runout_thrower']=array(
										'actual'	=>	$value->runout_thrower,
										'points'	=> 	$value->runout_thrower_point
									);

									$playerRecord['runout_catcher']=array(
										'actual'	=>	$value->runout_catcher,
										'points'	=> 	$value->runout_catcher_point
									);

									$playerRecord['total_point']=array(
										'actual'	=>	'',
										'points'	=> 	$value->total_point
									);	
								}
							}
							$dreamPlayers	=	$this->DreamTeams->find()->where(['series_id'=>$series_id,'match_id'=>$match_id,'player_id'=>$row->playerId])->first();
							
							$result[] = array(
								'player_id' 		=>	$row->playerId,
								'player_name'		=>	$val['player_name'],
								'player_image'		=>	$val['player_image'],
								'player_credit'		=>	$val['player_credit'],
								'selection_percent' =>	$percent,
								'points'			=>	$row->point,
								'in_contest'		=>	!empty($teamNo) ? true : false,
								'team_number'		=>	$teamNo, //$teamNum, //array_unique($teamNo)
								'player_breckup'	=>	$playerRecord,
								'in_dream_team'		=>	!empty($dreamPlayers) ? true : false
							);
							
						}
						// die;
						$data1	=	$result;
						$status	=	true;
						$message=	__("Success.", true);
						// die;
					} else {
						$message	=	__("Match scheduled to start soon. Refresh shortly to see fantasy scores of players.", true);
					}
				} else {
					$message	=	__('Invalid user id.',true);
				}
			} else {
				$message	=	__("User id, language, Series id, Match id are Empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);	
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function friendReferalDetail() {
		$status		=	false;
		$message	=	NULL;
		$data		=	array();
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('ReferalCodeDetails');
		$this->loadModel('Users');
		if($decoded) {
			if(!empty($decoded['user_id']) && !empty($decoded['language'])) {
				$authUser	=	$this->Users->find()->where(['id'=>$decoded['user_id'],'status'=>ACTIVE])->first();
				if(!empty($authUser)) {
					$user	=	$this->ReferalCodeDetails->find()
								->where(['refered_by'=>$decoded['user_id'],'Users.status'=>ACTIVE])
								->contain(['Users'=>[
									'fields'=>['image','team_name']
								]])->toArray();
					$userDetail	=	[];
					$totalEarnd	=	$toBeEarned	=	$userAmount	=	0;
					if(!empty($user)) {
						$counter	=	0;
						foreach($user as $referedUser) {
							if(!empty($referedUser->user)) {
								$rootPath	=	WWW_ROOT.'uploads'.DS.'users'.DS;
								if($referedUser->user->image_updated){
									$filePath	=	SITE_URL.'uploads/users/';
								} else {
									$filePath	=	SITE_URL.'uploads/avetars/';
								}
								
								$userImage	=	'';
								if(!empty($referedUser->user->image) && file_exists($rootPath.$referedUser->user->image)) {
									$userImage	=	$filePath.$referedUser->user->image;
								}
								$userDetail[$counter]['image']			=	$userImage;
								$userDetail[$counter]['team_name']		=	$referedUser->user->team_name;
								$userDetail[$counter]['received_amount']=	$referedUser->refered_by_amount;
								$userDetail[$counter]['total_amount']	=	$referedUser->user_amount;
								
								$counter++;
							}
							$totalEarnd	+=	$referedUser->refered_by_amount;
							$userAmount	+=	$referedUser->user_amount;
						}
					}
					$toBeEarned	=	$userAmount - $totalEarnd;
					$data1->total_earnd		=	$totalEarnd;
					$data1->to_be_earnd		=	$toBeEarned;
					$data1->total_fields	=	count($userDetail);
					$data1->friend_detail	=	$userDetail;
					$data1->referral_amount	=	Configure::read('Admin.setting.referral_bouns_amount_referral');
					$status		=	true;
				} else {
					$message	=	__('Invalid user id.',true);
				}
			} else {
				// admin_percentage
				$message	=	__('Please check user is correct or not.',true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);	
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function generatePaytmChecksum() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		
		require_once(ROOT . DS . 'vendor' . DS  . 'PaytmKit' .DS. 'lib'. DS . 'encdec_paytm.php');
		if(!empty($decoded)) {
			$setting	=	Configure::read('Admin.setting');
			if(!empty($decoded['CUST_ID'])) {
				$merchantKey	=	$setting['paytm_merchant_key'];
				$paytmParams	=	$decoded;
				$paytmChecksum	=	getChecksumFromArray($paytmParams, $merchantKey);
				
				$data1->checksum	=	$paytmChecksum;
				$status		=	true;
			} else {
				$message	=	__('Please check all details are correct or not.',true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function withdrawCash() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Users');
		if(!empty($decoded)) {
			if(!empty($decoded['user_id'])) {
				$user	=	$this->Users->find()->where(['Users.id'=>$decoded['user_id'],'status'=>ACTIVE])->contain(['BankDetails','PenAadharCard'])->first();
				if(!empty($user)) {
					$data['mobile_no']		=	$user->phone;
					$data['email']			=	($user->email_verified == true) ? $user->email : '';
					$data['email_verify']	=	 ($user->email_verified == true) ? true : false;
					
					if(!empty($user->pen_aadhar_card) && $user->pen_aadhar_card->is_verified == 0) {
						$data['pen_verify']	=	1;
					} elseif(!empty($user->pen_aadhar_card) && $user->pen_aadhar_card->is_verified == 1) {
						$data['pen_verify']	=	2;
					} else {
						$data['pen_verify']	=	0;
					}
					if(!empty($user->bank_detail) && $user->bank_detail->is_verified == 0) {
						$data['bank_account_verify']	=	1;
					} elseif(!empty($user->bank_detail) && $user->bank_detail->is_verified == 1) {
						$data['bank_account_verify']	=	2;
					} else {
						$data['bank_account_verify']	=	0;
					}
					$status	=	true;
					$data1	=	$data;
				} else {
					$message	=	__('Invalid user id.',true);
				}
			} else {
				$message	=	__('Please check user id is empty.',true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function verifyAccountEmail($verifyStr = null) {
		$this->loadModel('Users');
		$user	=	$this->Users->find()->where(['verify_string'=>$verifyStr,'status'=>ACTIVE])->first();
		if(!empty($user)) {
			$user->verify_string	=	'';
			$user->email_verified	=	true;
			$this->Users->save($user);
			echo '<h2 style="text-align:center;">Your account verified successfully.</h2>'; die;
		} else {
			echo '<h2 style="text-align:center;">Verification link is not valid.</h2>'; die;
		}
	}

	public function verifyEmail() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Users');
		$this->loadModel('EmailTemplates');
		$this->loadModel('WithdrawRequests');
		if(!empty($decoded)) {
			if(!empty($decoded['user_id']) && !empty($decoded['email'])) {
				 $user	=	$this->Users->find()->where(['Users.id'=>$decoded['user_id'],'status'=>ACTIVE])->first();
				if(!empty($user)) {
					$emailTemplate	=	$this->EmailTemplates->find()->where(['subject'=>'confirm_your_account'])->first();
					if(!empty($emailTemplate)) {
						$to			=	$decoded['email'];
						$from		=	Configure::read('Admin.setting.admin_email');
						$subject	=	$emailTemplate->email_name;
						$verifyStr	=	time().base64_encode($decoded['email']);
						$resetUrl	=	SITE_URL.'WebServices/verify-account-email/'.$verifyStr;
						$resetLink	=	'<a href="'.$resetUrl.'">Click Here To Verify Now</a>';
						$message1	=	str_replace(['{{site_name}}','{{link}}'],['',$resetLink],$emailTemplate->template);
						//$this->sendMail($to, $subject, $message1, $from);
						$user->verify_string	=	$verifyStr;
						$this->Users->save($user);
						$status		=	true;
						$message	=	__('We sent you to verify your email, Please click on the verification link in the mail',true);							
					} else {
						$message	=	__('Email could not sent.',true);
					}
				} else {
					$message	=	__('Invalid user id.',true);
				}
			} else {
				$message	=	_('Please check user id is empty.',true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function verifyPanDetail() {
		
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	$this->request->data['data'];
		$decoded    =	json_decode($data_row, true);
		$imgDecoded	=	$this->request->data;

		$this->log("request ".print_r($this->request->data, true), 'debug');
		$this->log("Pan card image ".print_r($imgDecoded, true), 'debug');
		
		$this->loadModel('Users');
		$this->loadModel('PenAadharCard');
		if(!empty($decoded)) {
			if(!empty($decoded['user_id']) && !empty($decoded['pan_name']) && !empty($decoded['pan_number']) && !empty($decoded['date_of_birth']) && !empty($decoded['state']) && !empty($imgDecoded['image']) ) {
				$authUser	=	$this->Users->find()->where(['id'=>$decoded['user_id'],'status'=>ACTIVE])->first();
				if(!empty($authUser)) {

					$pan_number    = (isset($decoded['pan_number'])) ? $decoded['pan_number'] : '';

					if( $pan_number != '' ) {

                        if (!preg_match("/^([a-zA-Z]){5}([0-9]){4}([a-zA-Z]){1}?$/", $pan_number)) {
                            $response_data = array('status' => $status, 'message' => 'Invalid PAN number, please enter correct number.', 'data' => $data1);
                            echo json_encode(array('response' => $response_data));
                            die;
                        }

                        /* $panexist = $this->PenAadharCard->find()->where([ 'pan_card' => $pan_number, 'is_verified != '=> 2 ])->count();
                        if ( $panexist > 0 ) {
                            $response_data = array('status' => $status, 'message' => 'This PAN number already exist, please try with other PAN card.', 'data' => $data1);
                            echo json_encode(array('response' => $response_data));
                            die;
                        } */
                    } else {
                        $response_data = array('status' => $status, 'message' => 'Please enter PAN number.', 'data' => $data1);
                        echo json_encode(array('response' => $response_data));
                        die;
                    }

					// $penAadharCard	=	$this->PenAadharCard->newEntity();
					$penAadharCard	=	$this->PenAadharCard->find()->where(['user_id'=>$decoded['user_id']])->first();
					if(empty($penAadharCard)) {
						$penAadharCard	=	$this->PenAadharCard->newEntity();
					}


					if(!empty($imgDecoded['image'])) {

						$file		=	$imgDecoded['image'];

						$allowed_image_extension = array(
							"png",
							"jpg",
							"jpeg",
							"PNG",
							"JPG",
							"JPEG"
						);

						// Get image file extension
						$file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
						
						// Validate file input to check if is not empty
						if (! file_exists($file["tmp_name"])) {
							$message	=	__('Please upload image.',true);
						} else if (! in_array($file_extension, $allowed_image_extension)) { // Validate file input to check if is with valid extension
							$message	=	__('Upload valid image. Only PNG and JPEG are allowed.',true);
						} else if (($file["size"] > 5000000)) { // Validate image file size
							$message	=	__('You cannot upload more than 5MB Image.',true);
						} else {

							$fileArr	=	explode('.',$file['name']);
							$ext		=	end($fileArr);
							$fileName	=	time().$decoded['user_id'].'.'.$ext;
							$filePath	=	$filePath	=	WWW_ROOT .'uploads/pan_image/'.$fileName;
							move_uploaded_file($file['tmp_name'],$filePath);
							$penAadharCard->pan_image	=	$fileName;

							$penAadharCard->user_id			=	$decoded['user_id'];
							$penAadharCard->pan_name		=	$decoded['pan_name'];
							$penAadharCard->pan_card		=	$pan_number;
							$penAadharCard->date_of_birth	=	date('Y-m-d',strtotime($decoded['date_of_birth']));
							$penAadharCard->state			=	$decoded['state'];
							$penAadharCard->aadhar_card		=	isset($decoded['aadhar_card']) ? $decoded['aadhar_card'] : 0;
							$penAadharCard->created			=	date('Y-m-d H:i:s');
							$penAadharCard->is_verified		=	0;
							if($result = $this->PenAadharCard->save($penAadharCard)) {
								$status		=	true;
								$message	=	__('Your PAN details have been submitted.',true);
								$result->date_of_birth	=	date('d-m-Y',strtotime($result->date_of_birth));
								$data1		=	$result;
							}
						}
					} else {
						$message	=	__('Please upload image.',true);
					}
				} else {
					$message	=	__('Invalid user id.',true);
				}
			} else {
				$message	=	__('Please check all details are filled correct.',true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function verifyBankDetail() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	$this->request->data['data'];
		$decoded    =	json_decode($data_row, true);
		$imgDecoded	=	$this->request->data;
		$this->loadModel('Users');
		$this->loadModel('BankDetails');
		if(!empty($decoded)) {
			if(!empty($decoded['user_id']) && !empty($decoded['account_no']) && !empty($decoded['ifsc_code']) && !empty($decoded['bank_name']) && !empty($decoded['branch']) && !empty($imgDecoded['image'])) {
				$authUser	=	$this->Users->find()->where(['id'=>$decoded['user_id'],'status'=>ACTIVE])->first();
				if(!empty($authUser)) {
					
					$bankDetail	=	$this->BankDetails->find()->where(['user_id'=>$decoded['user_id']])->first();
					if(empty($bankDetail)) {
						$bankDetail	=	$this->BankDetails->newEntity();
					}

					if(!empty($imgDecoded['image'])) {
						$file		=	$imgDecoded['image'];

						$allowed_image_extension = array(
							"png",
							"jpg",
							"jpeg",
							"PNG",
							"JPG",
							"JPEG"
						);

						// Get image file extension
						$file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
						
						// Validate file input to check if is not empty
						if (! file_exists($file["tmp_name"])) {
							$message	=	__('Please upload image.',true);
						} else if (! in_array($file_extension, $allowed_image_extension)) { // Validate file input to check if is with valid extension
							$message	=	__('Upload valid image. Only PNG and JPEG are allowed.',true);
						} else if (($file["size"] > 5000000)) { // Validate image file size
							$message	=	__('You cannot upload more than 5MB Image.',true);
						} else {
							
							$fileArr	=	explode('.',$file['name']);
							$ext		=	end($fileArr);
							$fileName	=	time().$decoded['user_id'].'.'.$ext;
							$filePath	=	WWW_ROOT .'uploads/bank_proof/'.$fileName;
							move_uploaded_file($file['tmp_name'],$filePath);
							$bankDetail->bank_image	=	$fileName;

							$bankDetail->user_id		=	$decoded['user_id'];
							$bankDetail->account_number	=	$decoded['account_no'];
							$bankDetail->ifsc_code		=	$decoded['ifsc_code'];
							$bankDetail->bank_name		=	$decoded['bank_name'];
							$bankDetail->branch			=	$decoded['branch'];
							$bankDetail->created		=	date('Y-m-d H:i:s');
							$bankDetail->is_verified	=	0;
							if($result = $this->BankDetails->save($bankDetail)) {
								$status		=	true;
								$message	=	__('Your Bank detail have been submitted.',true);
								$data1		=	$result;
							}
							
						}
					} else {
						$message	=	__('Please upload image.',true);
					}


				} else {
					$message	=	__('Invalid user id.',true);
				}
			} else {
				$message	=	__('Please check all details are filled correct.',true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function seriesRanking() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Users');
		$this->loadModel('BankDetails');
		if(!empty($decoded)) {
			if(!empty($decoded['user_id']) && !empty($decoded['series_id'])) {
				$user	=	$this->Users->find()
							->where(['id'=>$decoded['user_id'],'status'=>ACTIVE])
							->select(['image','team_name'])->first();
				if(!empty($user)) {
					$myRankPoints	=	$this->getSeriesRanking($decoded['user_id'],$decoded['series_id']);
					
					if(!empty($myRankPoints)) {
						foreach($myRankPoints as $key=>$teamPoints) {
							$rootPatch	=	WWW_ROOT.'uploads'.DS.'users'.DS;
							$userImage	=	'';
							if(!empty($user->image) && file_exists($rootPatch.$user->image)) {
								if( $user->image_updated ){
									$userImage	=	SITE_URL.'uploads/users/'.$user->image;
								} else {
									$userImage	=	SITE_URL.'uploads/avetars/'.$user->image;
								}
							}
							$myRankPoints[$key]['user_id']		=	(int) $decoded['user_id'];
							$myRankPoints[$key]['team_name']	=	!empty($user) ? $user->team_name : '';
							$myRankPoints[$key]['user_image']	=	$userImage;
						}
					}
					
					$rankPoints	=	$this->getSeriesRanking(0,$decoded['series_id']);
					
					if(!empty($rankPoints)) {
						foreach($rankPoints as $key => $points) {
							if($points['user_id'] == $decoded['user_id']) {
								unset($rankPoints[$key]);
							}
						}
					}
					$status	=	true;
					$data1	=	array_merge($myRankPoints,$rankPoints);
				} else {
					$message	=	__('Invalid user id.',true);
				}
			} else {
				$message	=	__('Please check all details are filled correct.',true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function teamStates() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Users');
		$this->loadModel('Series');
		$this->loadModel('PlayerTeams');
		if(!empty($decoded)) {
			if(!empty($decoded['user_id']) && !empty($decoded['series_id'])) {
				$user	=	$this->Users->find()
							->where(['id'=>$decoded['user_id'],'status'=>ACTIVE])
							->select(['id','team_name','image'])->first();
				
					$allSeriesTeam	=	$this->PlayerTeams->find()
										->where(['PlayerTeams.series_id'=>$decoded['series_id'],'user_id'=>$decoded['user_id'],'points !='=>0])
										->group(['PlayerTeams.match_id'])
										->order(['SeriesSquad.date'=>'DESC','SeriesSquad.time'=>'DESC','PlayerTeams.points'=>'DESC'])
										->contain(['SeriesSquad'=>['LocalMstTeams','VisitorMstTeams']])
										->toArray();
					$teamName	=	$userImage	=	$seriesName	=	$totalPoints	=	$totalRank	=	'';
					if(!empty($user)) {
						$teamName		=	$user->team_name;
						$rootPath		=	WWW_ROOT.'uploads'.DS.'users'.DS;
						if(!empty($user->image) && file_exists($rootPath.$user->image)) {
							if( $user->image_updated ){
								$userImage	=	SITE_URL.'uploads/users/'.$user->image;
							} else {
								$userImage	=	SITE_URL.'uploads/avetars/'.$user->image;
							}
						}
					}
					$myRankPoints	=	$this->getSeriesRanking($decoded['user_id'],$decoded['series_id']);
					if(!empty($myRankPoints)) {
						foreach($myRankPoints as $myRank) {
							$seriesName		=	$myRank['series_name'];
							$totalPoints	=	$myRank['points'];
							$totalRank		=	$myRank['rank'];
						}
					}
					$matches	=	[];
					if(!empty($allSeriesTeam)) {
						foreach($allSeriesTeam as $key=> $seriesTeam) {
							$teamPoints	=	$this->PlayerTeams->find()
											->where(['PlayerTeams.series_id'=>$seriesTeam->series_id,'PlayerTeams.match_id'=>$seriesTeam->match_id,'user_id'=>$seriesTeam->user_id,'points !='=>0])
											->order(['PlayerTeams.points'=>'DESC','SeriesSquad.date'=>'DESC','SeriesSquad.time'=>'DESC'])
											->contain(['SeriesSquad'])
											->first();
							$matches[$key]['local_team']	=	!empty($seriesTeam->series_squad->local_mst_team->team_short_name) ? $seriesTeam->series_squad->local_mst_team->team_short_name : $seriesTeam->series_squad->local_mst_team->team_name;
							$matches[$key]['visitor_team']	=	!empty($seriesTeam->series_squad->visitor_mst_team->team_short_name) ? $seriesTeam->series_squad->visitor_mst_team->team_short_name : $seriesTeam->series_squad->visitor_mst_team->team_name;
							$matches[$key]['team_count']	=	'T'.$teamPoints->team_count;
							$matches[$key]['points']		=	$teamPoints->points;
							$matches[$key]['match_id']		=	$teamPoints->match_id;
						}
					}
				
					$data['team_name']		=	$teamName;
					$data['image']			=	$userImage;
					$data['series_name']	=	$seriesName;
					$data['total_points']	=	$totalPoints;
					$data['totalRank']		=	$totalRank;
					$data['point_detail']	=	$matches;
					$status	=	true;
					$data1	=	$data;
				
			} else {
				$message	=	__('Please check all details are filled correct.',true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function teamProfileComparision() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Users');
		$this->loadModel('PlayerTeams');
		$this->loadModel('LiveScore');
		$this->loadModel('PlayerTeamContests');
		if(!empty($decoded)) {
			if(!empty($decoded['user_id']) && !empty($decoded['friend_user_id'])) {
				$authUser	=	$this->Users->find()->where(['id'=>$decoded['user_id'],'status'=>ACTIVE])->first();
				if(!empty($authUser)) {
					$user	=	$this->Users->find()->where(['id'=>$decoded['friend_user_id'],'status'=>ACTIVE])->first();
					$teamName	=	'';
					if(!empty($user)) {
						$teamName	=	$user->team_name;
						$userImage	=	'';
						$rootPath	=	WWW_ROOT.'uploads'.DS.'users'.DS;
						if(!empty($user->image) && file_exists($rootPath.$user->image)) {
							if( $user->image_updated ){
								$userImage		=	SITE_URL.'uploads/users/'.$user->image;
							} else {
								$userImage		=	SITE_URL.'uploads/avetars/'.$user->image;
							}
						}
						$currentDate	=	date('Y-m-d');
						$completeDate	=	date('Y-m-d',strtotime('-1 week'));
						$contestFilter	=	['user_id'=>$decoded['friend_user_id'],'SeriesSquad.match_status'=>MATCH_FINISH,'SeriesSquad.date <=' => $currentDate,'SeriesSquad.status'=>ACTIVE];
						
						$contestCount	=	$this->PlayerTeamContests->find()
											->where([$contestFilter])
											->group(['PlayerTeamContests.series_id','PlayerTeamContests.match_id','PlayerTeamContests.contest_id'])
											->contain(['SeriesSquad'])->count();
						
						$paidContests	=	$this->PlayerTeamContests->find()
											->where(['user_id'=>$decoded['user_id'],'Contest.contest_type LIKE'=>'Paid'])
											->group(['PlayerTeamContests.series_id','PlayerTeamContests.match_id','PlayerTeamContests.contest_id'])
											->contain(['Contest'])->count();
						
						$totalMatches	=	$this->PlayerTeamContests->find()
											->where([$contestFilter])
											->group(['PlayerTeamContests.series_id','PlayerTeamContests.match_id'])
											->contain(['SeriesSquad'])->count();
						
						$toalSeries		=	$this->PlayerTeamContests->find()
											->where([$contestFilter])
											->group(['PlayerTeamContests.series_id'])
											->contain(['SeriesSquad'])->count();
						
						$totalSeriesWin	=	$this->PlayerTeamContests->find()
											->where([$contestFilter,'AND'=>[['winning_amount !='=>''],['winning_amount !='=>'0']]])
											->contain(['SeriesSquad'])->count();
					
						$level	=	1;
						if(!empty($paidContests)) {
							$ratio		=	$paidContests / 20;
							$ratioPlus	=	(int) $ratio + 1;
							if((int) $ratio < $ratioPlus) {
								$level	=	$ratioPlus;
							}
						}
					}
					
					// recent match performance that are played by both self and friend
					$joinedSeries	=	$this->PlayerTeamContests->find()
										->where(['PlayerTeamContests.user_id'=>$decoded['user_id'],'SeriesSquad.match_status'=>MATCH_FINISH,'SeriesSquad.date <=' => $currentDate,'SeriesSquad.status'=>ACTIVE])
										->group(['PlayerTeamContests.series_id','PlayerTeamContests.match_id'])
										->order(['PlayerTeams.points'=>'DESC'])
										->contain(['PlayerTeams'=>['SeriesSquad'=>['LocalMstTeams','VisitorMstTeams']]])->toArray();
					// pr($joinedSeries);
					$performance	=	[];
					if(!empty($joinedSeries)) {
						$flag	=	0;
						foreach($joinedSeries as $contestSeries) {
							$series	=	$contestSeries->player_team;
							$friendSeries	=	$this->PlayerTeams->find()
												->where(['user_id'=>$decoded['friend_user_id'],'PlayerTeams.series_id'=>$series['series_id'],'PlayerTeams.match_id'=>$series['match_id']])
												->order(['points'=>'DESC'])
												->first();
							
							// if(!empty($friendSeries)) {
								$liveScore	=	$this->LiveScore->find()->where(['seriesId'=>$series['series_id'],'matchId'=>$series['match_id']])->select(['comment'])->first();
								
								$visitorTeamFlag=	'';
								$localTeamFlag	=	'';
								$flagRootPath	=	WWW_ROOT.'uploads'.DS.'team_flag'.DS;
								
								$visitorTeam	=	$series->series_squad->visitor_mst_team;
								$localTeam		=	$series->series_squad->local_mst_team;
								$visitorTeamName=	!empty($visitorTeam->team_short_name) ? $visitorTeam->team_short_name : $visitorTeam->team_name;
								$localTeamName	=	!empty($localTeam->team_short_name) ? $localTeam->team_short_name : $localTeam->team_name;
								
								if(!empty($localTeam->flag) && file_exists($flagRootPath.$localTeam->flag)) {
									$localTeamFlag	=	SITE_URL.'uploads/team_flag/'.$localTeam->flag;
								}
								if(!empty($visitorTeam->flag) && file_exists($flagRootPath.$visitorTeam->flag)) {
									$visitorTeamFlag	=	SITE_URL.'uploads/team_flag/'.$visitorTeam->flag;
								}
								$matchDate	=	date('Y-m-d',strtotime($series->series_squad->date));
								$comment	=	!empty($liveScore) ? $liveScore->comment : '';
								
							// }
							$performance[$flag]['visitor_team_name']=	$visitorTeamName;
							$performance[$flag]['visitor_team_flag']=	$visitorTeamFlag;
							$performance[$flag]['local_team_name']	=	$localTeamName;
							$performance[$flag]['local_team_flag']	=	$localTeamFlag;
							$performance[$flag]['match_date']		=	$matchDate;
							$performance[$flag]['match_comment']	=	$comment;
							$performance[$flag]['my_points']		=	(double) $series->points;
							$performance[$flag]['my_team']			=	(int) $series->team_count;
							$performance[$flag]['friend_points']	=	(double) !empty($friendSeries) ? $friendSeries->points : 0;
							$performance[$flag]['friend_team']		=	(int) !empty($friendSeries) ? $friendSeries->team_count : 0;
							$flag++;
						}
					}
					$data['team_name']			=	$teamName;
					$data['image']				=	$userImage;
					$data['contest_level']		=	$level;
					$data['contest_finished']	=	$contestCount;
					$data['total_match']		=	$totalMatches;
					$data['total_series']		=	$toalSeries;
					$data['series_wins']		=	$totalSeriesWin;
					$data['recent_performance']	=	$performance;
					$status		=	true;
					$data1		=	$data;
				} else {
					$message	=	__('Invalid user id.',true);
				}
			} else {
				$message	=	__('Please check all details are filled correct.',true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}

	public function leaderboard(){
		header('Content-Type: application/json');
		$totalTeam  =   $persent  =  '0'; 
		$status		=	false;
		$rslt		=	false;
		$message	=	NULL;
		$data		=	array();
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		
		$this->loadModel('PlayerTeamContests');
		$this->loadModel('MatchContest');
		$this->loadModel('SeriesSquad');
		$this->loadModel('Users');
		if($decoded) {
			if(!empty($decoded['user_id']) && !empty($decoded['language']) && !empty($decoded['series_id']) && !empty($decoded['match_id']) && !empty($decoded['contest_id']) ) {
				$user_id	=	$decoded['user_id'];
				$series_id	=	$decoded['series_id'];
				$match_id	=	$decoded['match_id'];
				$contest_id	=	$decoded['contest_id'];
				$authUser	=	$this->Users->find()->where(['id'=>$decoded['user_id'],'status'=>ACTIVE])->first();
				if(!empty($authUser)) {
					$details	=	$this->MatchContest->find()
									->where(['SeriesSquad.match_id'=>$match_id,'contest_id'=>$contest_id])
									->contain(['SeriesSquad','Contest'=>['CustomBreakup']])
									->first();
					$invite_code=	$details['invite_code'];
					$entry_fee	=	$details['contest']['entry_fee'];
					$win_amount	=	$details['contest']['winning_amount'];

					$users	=	$this->SeriesSquad->find()->where(['series_id'=>$series_id,'match_id'=>$match_id])->first();
					$match  =   $users['localteam'].' vs '.$users['visitorteam'];

					$query	=	$this->PlayerTeamContests->find()
								->contain(['PlayerTeams'=>['PlayerTeamDetails']])
								->WHERE(['PlayerTeamContests.match_id'=>$match_id,'PlayerTeamContests.series_id'=>$series_id,'PlayerTeamContests.contest_id'=>$contest_id]);
					$record	=	$query->toArray();
					
					require_once(ROOT . DS . 'vendor' . DS  . 'TCPDF-master' . DS . 'tcpdf.php');
					$pdf	=	new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
					
					$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
					$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
					
					$pdf->SetFont('helvetica', '', 9);
					$pdf->AddPage('L', 'A4');
					
					$html = 
					'<html>
						<head></head>
						<body>
							<table border="1">
								<tr>
									<th>'.SITE_TITLE.'</th>
									<th colspan="4">'.$match.'</th>
									<th colspan="2">Contest: Win Rs. '.$win_amount.'</th>
									<th colspan="2">Entry Fee Rs.'.$entry_fee.'</th>
									<th>Members: 11</th>
									<th colspan="2">Invite code:'.$invite_code.'</th>
								</tr>
								<tr>
									<th>User(Team)</th>
									<th>Player 1 (Captain)</th>
									<th>Player 2 (Vice Captain)</th>
									<th>Player 3 </th>
									<th>Player 4 </th>
									<th>Player 5 </th>
									<th>Player 6 </th>
									<th>Player 7 </th>
									<th>Player 8 </th>
									<th>Player 9 </th>
									<th>Player 10 </th>
									<th>Player 11 </th>
								</tr>';
					$i	=	1;
					foreach($record as $key => $value) {
						$c1	=	$c2	=	'';					
						$teamDetails=	$this->Users->find()->where(['id'=>$value->user_id])->first();
						$team_name	=	$teamDetails->team_name;
						$captain	=	$value->player_team->captain;
						$viceCaptain=	$value->player_team->vice_captain;
						$teamNumber	=	$value->player_team->team_count;					
						
						$html .= '<tr><td>'.$team_name.' ('.$teamNumber.')'.'</td>';
						$plyDtl	=	$value->player_team->player_team_details;
						$teamPlayers	=	[];
						foreach($plyDtl as $playerKey => $playerValue) {
							$teamPlayers[$playerKey]	=	$playerValue->player_id;
						}
						if(($key = array_search($captain,$teamPlayers)) !== false) {
							$val		=	$this->getPlayerImage($captain);
							$playerName	=	$val['player_name'];
							$html .= '<td>'.$playerName.'</td>';
							unset($teamPlayers[$key]);
						}
						if(($key = array_search($viceCaptain,$teamPlayers)) !== false) {
							$val		=	$this->getPlayerImage($viceCaptain);
							$playerName	=	$val['player_name'];
							$html .= '<td>'.$playerName.'</td>';
							unset($teamPlayers[$key]);
						}
						foreach($teamPlayers as $v) {
							$playerId	=	$v;
							$val		=	$this->getPlayerImage($playerId);
							$playerName	=	$val['player_name'];
							$html .= '<td>'.$playerName.'</td>';
						}
						$html .= '</tr>';					
					}
					$html .='</table></body></html>';
					
					$name	=	'file'.time().'.pdf';
					$path	=	WWW_ROOT .'uploads/leaderboard/'.$name;
					$pdf->writeHTML($html, true, 0, true, 0);
					$pdf->lastPage();
					
					/////////////// Remove files from folder Start ///////////////
					$folder	=	'leaderboard';
					$files	=	glob($folder. '/*');
					foreach($files as $file){
						if(is_file($file)){
							unlink($file);
						}
					}
					/////////////// Remove files from folder End ///////////////
					$pdf->Output($path, 'F');
					
					$finalPDF	=	SITE_URL.'uploads/leaderboard/'.$name;
					
					$data1	=	array('url'=>$finalPDF);
					$status	=	true;
					$message=	__("Success.", true);
				} else {
					$message	=	__('Invalid user id.',true);
				}
			} else {
				$message	=	__("User id, language, Series id, Match id are Empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);	
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function notificationList() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Notifications');
		$this->loadModel('Users');
		if(!empty($decoded)) {
			if(!empty($decoded['user_id'])) {
				$authUser	=	$this->Users->find()->where(['id'=>$decoded['user_id'],'status'=>ACTIVE])->first();
				if(!empty($authUser)) {
					$notification	=	$this->Notifications->find()->where(['user_id IN'=>[$decoded['user_id'],0],'status'=>ACTIVE])->order(['date'=>'DESC'])->toArray();
					if(!empty($notification)) {
						foreach($notification as $record) {
							$matchData = unserialize($record->match_data);
							if($matchData==''){
								$matchData = (object) array();
							}
							$record->match_data	=	$matchData;
							$record->date	=	date('d F,Y',strtotime($record->date));
						}
					}
					$status		=	true;
					$data1		=	$notification;
				} else {
					$message	=	__('Invalid user id.',true);
				}
			} else {
				$message	=	__('Please check all details are filled correct.',true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}

	public function getreplacePlayer() {
		$status			=	false;
		$message		=	NULL;
		$is_replaced 	= 	false;
		$replaced_message 	= 	'';
		$data			=	[];
		$data1			=	(object) array();
		$data_row		=	file_get_contents("php://input");
		$decoded    	=	json_decode($data_row, true);
		

		$this->loadModel('Users');
		$this->loadModel('SeriesPlayers');
		$this->loadModel('SeriesSquad');
		$this->loadModel('PlayerTeams');
		$this->loadModel('PlayerTeamDetails');
		if(!empty($decoded)) {
			if(!empty($decoded['user_id']) && !empty($decoded['series_id']) && !empty($decoded['match_id']) && !empty($decoded['contest_id']) && !empty($decoded['team_no'])  ) {
				$authUser	=	$this->Users->find()->where(['id'=>$decoded['user_id'],'status'=>ACTIVE])->first();
				if(!empty($authUser)) {
					$user_id 		=	$decoded['user_id'];
					$series_id 		=	$decoded['series_id'];
					$match_id 		=	$decoded['match_id'];
					$contest_id 	=	$decoded['contest_id'];
					$team_no 		=	$decoded['team_no'];

					$result	=	$this->SeriesSquad->find()
						->select(['series_id','type','localteam_players','visitorteam_players','localteam_id','visitorteam_id','inning1st_status','inning2nd_status','inning_break_status'])
						->where(['SeriesSquad.match_id'=>$decoded['match_id']])
						->first();

					if(!empty($result)) {

						$substitute 	=	$this->PlayerTeams->find()->where(['user_id'=>$user_id,'series_id'=>$series_id,'match_id'=>$match_id,'team_count'=>$team_no,'trump_mode'=>1])->first();
						if(!empty($substitute)) {
							$data1->twelveth = $substitute->twelveth;
							$data1->replace_player_ids = $substitute->replace_player_ids;
							$is_replaced = ($substitute->substitute_status) ? true : false;
							if($is_replaced){
								$replaced_message 	= 	'You have already switched your Trump player';
							} else {
								//if( $result->inning1st_status == 2 && $result->inning2nd_status == 0 ){
								if( $result->inning_break_status == 1 ){
									// ok
								} else {
									$is_replaced = true;
									$replaced_message 	= 	'You can replace your Trump player beetween inning break only.';
								}
							}

							
							

							$playerIdsArray = [];
							$playerIdsArray = explode(',',$data1->replace_player_ids);
							$playerIdsArray[] = $data1->twelveth;
							
							$json_players_arry = [];
							$player_ids_array = [];
							if(!empty($result->localteam_players)){
								$localteam_players = json_decode($result->localteam_players,true);
								$json_players_arry[$localteam_players['team_id']] = $localteam_players['players'];
								if(!empty($localteam_players['players'])){
									foreach($localteam_players['players'] AS $key => $val){
										$player_ids_array[] = $val['player_id'];
									}
								}
							}
							if(!empty($result->visitorteam_players)){
								$visitorteam_players = json_decode($result->visitorteam_players,true);
								$json_players_arry[$visitorteam_players['team_id']] = $visitorteam_players['players'];
								if(!empty($visitorteam_players['players'])){
									foreach($visitorteam_players['players'] AS $key => $val){
										$player_ids_array[] = $val['player_id'];
									}
								}
							}

							$type			=	strtolower($result->type);
							$seriesPlayers	=	$this->SeriesPlayers->find()
							->select(['SeriesPlayers.id','SeriesPlayers.series_id','SeriesPlayers.series_name','SeriesPlayers.team_id','SeriesPlayers.team_name','SeriesPlayers.player_id','SeriesPlayers.player_name','SeriesPlayers.player_role','PlayerRecord.id','PlayerRecord.player_id','PlayerRecord.player_name','PlayerRecord.image','PlayerRecord.playing_role','PlayerRecord.teams','PlayerRecord.player_credit'])
							->where([$type=>'True','series_id'=>$result->series_id,'SeriesPlayers.player_id IN'=>$playerIdsArray ])
							->contain(['PlayerRecord'])
							->group(['PlayerRecord.player_id'])
							->toarray();
							//echo '<pre>';
							//print_r($seriesPlayers);die;
							if(!empty($seriesPlayers)){
								foreach($seriesPlayers as $players) {
									$json_player_info = ( isset( $json_players_arry[$players->team_id][$players->player_id] )) ? $json_players_arry[$players->team_id][$players->player_id] : [];

									if(!empty($json_player_info)){
										$players->player_role = $json_player_info['player_role'];
										$players->player_record->playing_role	=	$json_player_info['player_role'];
										$players->player_record->player_credit	=	(string)$json_player_info['player_credit'];

										$players->player_credit		=	(string)$json_player_info['player_credit'];
										$players->double_player_credit=(double)$json_player_info['player_credit'];
									}

									if( $players->player_id == $data1->twelveth ){
										$players->twelveth_player =	true;
									} else {
										$players->twelveth_player =	false;
									}

								}
							}

							$data1	=	$seriesPlayers;
							$status	=	true;
							
						}
					}
					$status		=	true;
				} else {
					$message	=	__('Invalid user id.',true);
				}
			} else {
				$message	=	__('Please check all details are filled correct.',true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1,'is_replaced'=>$is_replaced,'replaced_message'=>$replaced_message);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function replacePlayer() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);

		$this->loadModel('Users');
		$this->loadModel('SeriesSquad');
		$this->loadModel('PlayerTeams');
		$this->loadModel('PlayerTeamDetails');
		if(!empty($decoded)) {
			if(!empty($decoded['user_id']) && !empty($decoded['series_id']) && !empty($decoded['match_id']) && !empty($decoded['contest_id']) && !empty($decoded['team_no']) && !empty($decoded['player_id']) ) {


				$result	=	$this->SeriesSquad->find()
						->select(['inning_break_status'])
						->where([ 'SeriesSquad.match_id'=>$decoded['match_id'] ])
						->first();

				if(!empty($result)) {
					if( $result->inning_break_status == 0 ){
						$response_data	=	array('status'=>$status,'message'=>'Oops! Innings break over.','data'=>$data1);
						echo json_encode(array('response' => $response_data));
						die;
					}
				}

                        

				$authUser	=	$this->Users->find()->where(['id'=>$decoded['user_id'],'status'=>ACTIVE])->first();

				if(!empty($authUser)) {
					$user_id 		=	$decoded['user_id'];
					$series_id 		=	$decoded['series_id'];
					$match_id 		=	$decoded['match_id'];
					$contest_id 	=	$decoded['contest_id'];
					$team_no 		=	$decoded['team_no'];
					$player_id 		=	$decoded['player_id'];

					$substitute 	=	$this->PlayerTeams->find()->where( [ 'user_id'=>$user_id, 'series_id'=>$series_id, 'match_id'=>$match_id, 'team_count'=>$team_no, 'trump_mode'=>1 ] )->first();

					if(!empty($substitute)) {
						$recd			=	$this->PlayerTeams->get($substitute->id);
						$table_id 		= $substitute->id;
						$substitute_id 	= $substitute->twelveth;
						

						if( $substitute_id > 0 && $table_id > 0 && $player_id > 0 ){

							$recd['replaced_by']	=	$player_id;
							$recd['substitute_status']	=	'1';
							if($this->PlayerTeams->save($recd)) {
											
							}
							$rslt 	=	$this->PlayerTeamDetails->find()->where(['player_team_id'=>$table_id,'player_id'=>$player_id])->first();
							if(!empty($rslt)){
								$result	=	$this->PlayerTeamDetails->get($rslt->id);
								$result['player_id'] = $substitute_id;
								if($this->PlayerTeamDetails->save($result)) {
											
								}
							}

						}
					}
					$status		=	true;
				} else {
					$message	=	__('Invalid user id.',true);
				}
			} else {
				$message	=	__('Please check all details are filled correct.',true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function dreamTeam() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Users');
		$this->loadModel('DreamTeams');
		if(!empty($decoded)) {
			if(!empty($decoded['match_id']) && !empty($decoded['series_id'])) {
				$series_id	=	$decoded['series_id'];
				$match_id	=	$decoded['match_id'];				
				$authUser	=	$this->Users->find()->where(['id'=>$decoded['user_id'],'status'=>ACTIVE])->first();
				if(!empty($authUser)) {
					$checkDreamTeam	=	$this->DreamTeams->find()
										->where(['series_id'=>$series_id,'match_id'=>$match_id])->toArray();
					if(empty($checkDreamTeam)) {
						$this->saveDreamTeam($match_id,$series_id);
					}
					
					$dreamTeam	=	$this->DreamTeams->find()
									->where(['series_id'=>$series_id,'match_id'=>$match_id])
									->select(['DreamTeams.player_id','DreamTeams.points','PlayerRecord.player_name','PlayerRecord.image','PlayerRecord.playing_role','PlayerRecord.player_credit'])
									->contain(['PlayerRecord'])->toArray();
					$array		=	[];
					$playerData	=	[];
					if(!empty($dreamTeam)) {
						foreach($dreamTeam as $team) {
							$islocalTeam	=	$this->getLocalTeam($series_id,$match_id,$team->player_id);
							$playerImage	=	$team->player_record['image'];
							$rootPath		=	WWW_ROOT.'uploads'.DS.'player_image'.DS;
							$image			=	'';
							if(!empty($playerImage) && file_exists($rootPath.$playerImage)) {
								$image	=	SITE_URL.'uploads/player_image/'.$playerImage;
							}
							$array[]	=	array(
								'player_id'	=>	$team->player_id,
								'point'		=>	(double) $team->points,
								'name'		=>	$team->player_record['player_name'],
								'image'		=>	$image,
								'role'		=>	!empty($team->player_record->playing_role) ? $team->player_record->playing_role :'Batsman',
								'credit'	=>	!empty($team->player_record->player_credit) ? $team->player_record->player_credit : '7',
								'is_local_team'	=>	$islocalTeam,
							);
						}
						$point	=	[];
						foreach($array as $key => $row) {
							$point[$key]  = $row['point'];
						}
						array_multisort($point, SORT_DESC, $array);
						$playerData['captain_player_id']		=	$array[0]['player_id'];
						$playerData['vice_captain_player_id']	=	$array[1]['player_id'];
						$totalPoints	=	0;
						if(!empty($array)) {
							foreach($array as $key1 => $details) {
								if($details['player_id'] == $playerData['captain_player_id']) {
									$details['point']	=	$details['point'] * 2;
									$array[$key1]['point']	=	$details['point'];
								} else 
								if($details['player_id'] == $playerData['vice_captain_player_id']) {
									$details['point']	=	$details['point'] * 1.5;
									$array[$key1]['point']	=	$details['point'];
								}
								$totalPoints	+=	$details['point'];
							}
						}
						$playerData['total_points']		=	$totalPoints;
						$playerData['player_details']	=	$array;
					}
					if(!empty($playerData)) {
						$status	=	true;
						$data1	=	$playerData;
					} else {
						$message	=	__("Dream team not found.", true);
					}
				} else {
					$message	=	__('Invalid user id.',true);
				}
			} else {
				$message	=	__("match id or series id are empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}

	public function bannerList() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Banners');
		$this->loadModel('SeriesSquad');
		$this->loadModel('MatchContest');
		$this->loadModel('PaymentOffers');
		if(!empty($decoded)) {
			
			$banners = $this->Banners->find()->where(['status'=>'1'])->order(['sequence'=>'ASC'])->toArray();
			if(!empty($banners)){
				foreach ($banners as $key => $value) {
					$upcoming	=	(object) [];
					$offer		=	(object) [];
					if($value->banner_type == 3){
						$currentDateTime=	date('Y-m-d H:i:s');
						$couponData		=	$this->PaymentOffers->find()->where(['id'=>$value->offer_id,'expiry_date >='=>$currentDateTime,'status'=>ACTIVE])->first();
						if(!empty($couponData)) {
							$offer->coupon_id		=	$couponData->id;
							$offer->coupon_code		=	$couponData->coupon_code;
							$offer->min_amount 		= !empty($couponData->min_amount) ? (float) $couponData->min_amount : 0;
							$offer->in_percentage	=	($couponData->max_cashback_percent > 0) ? true : false;
							$offer->created			=	date('Y-m-d H;i:s');
							$offer->discount_amount	=	$couponData->max_cashback_percent;
							$offer->max_discount	=	$couponData->max_cashback_amount;
						}
					}

					if($value->banner_type == 1) {
						$currentDate	=	date('Y-m-d');
						$oneMonthDate	=	date('Y-m-d',strtotime('+4 Days'));
						$currentTime	=	date('H:i', strtotime('+30 min'));
						$upCommingMatch	=	$this->SeriesSquad->find()->where(['OR'=>[['date'=>$currentDate,'time >= '=>$currentTime],['date > '=>$currentDate]],'series_id'=>$value->series_id,'match_id'=>$value->match_id,'Series.status'=>ACTIVE,'SeriesSquad.status'=>ACTIVE])->contain(['Series','LocalMstTeams','VisitorMstTeams'])->order(['date','time'])->first();
						
						if(!empty($upCommingMatch)) {
							$totalContest	=	$this->MatchContest->find()->where(['match_id'=>$value->match_id])->count();
							
							$filePath		=	WWW_ROOT.'uploads/team_flag/';
							$localTeamFlag	=	$visitorTeamFlag	=	'';
							if(!empty($upCommingMatch->local_mst_team) && file_exists($filePath.$upCommingMatch->local_mst_team->flag)) {
								$localTeamFlag	=	SITE_URL.'uploads/team_flag/'.$upCommingMatch->local_mst_team->flag;
							}
							if(!empty($upCommingMatch->visitor_mst_team) && file_exists($filePath.$upCommingMatch->visitor_mst_team->flag)) {
								$visitorTeamFlag=	SITE_URL.'uploads/team_flag/'.$upCommingMatch->visitor_mst_team->flag;
							}
							$seriesName	=	!empty($upCommingMatch->series->short_name) ? $upCommingMatch->series->short_name : str_replace("Cricket ","",$upCommingMatch->series->name);
							$visitorTeam	=	$upCommingMatch->visitor_mst_team;
							$localTeam		=	$upCommingMatch->local_mst_team;
							$upcoming->series_id		=	$upCommingMatch->series_id;
							$upcoming->match_id			=	$upCommingMatch->match_id;
							$upcoming->guru_url			=	!empty($upCommingMatch->guru_url) ? $upComing->guru_url : '';
							$upcoming->series_name		=	$seriesName;
							$upcoming->local_team_id	=	$upCommingMatch->localteam_id;
							$upcoming->local_team_name	=	!empty($localTeam->team_short_name) ? $localTeam->team_short_name : $upCommingMatch->localteam;
							$upcoming->local_team_flag	=	$localTeamFlag;
							$upcoming->visitor_team_id	=	$upCommingMatch->visitorteam_id;
							$upcoming->visitor_team_name=	!empty($visitorTeam->team_short_name) ? $visitorTeam->team_short_name : $upCommingMatch->visitorteam;
							$upcoming->visitor_team_flag=	$visitorTeamFlag;
							$upcoming->star_date		=	$this->finalDate($upCommingMatch->date);
							$upcoming->star_time		=	date('H:i',strtotime($upCommingMatch->time.'-30 min'));
							$upcoming->total_contest	=	!empty($totalContest) ? $totalContest : 0;
						}
					}

					$data[] = array(
						'image'		=> 	SITE_URL.'uploads/banner_image/'.$value->image,
						'type'		=> 	$value->banner_type,
						'link'		=> 	$value->link,
						'page_title'=> 	$value->page_title,
						'offer'		=> 	$offer,
						'upcoming'	=> 	$upcoming,
					);
				}
			}

			$status	=	true;
			$data1	=	$data;
			
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	public function addWithdrawRequest() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Users');
		$this->loadModel('WithdrawRequests');
		$this->loadModel('AdminWallet');
		if(!empty($decoded)) {
			if(!empty($decoded['user_id']) && !empty($decoded['withdraw_amount']) && !empty($decoded['type'])) {
				$minWithdrawAmt	=	Configure::read('Admin.setting.min_withdraw_amount');
				if($minWithdrawAmt > $decoded['withdraw_amount']) {
					$message	=	__(sprintf('You are trying to withdraw less than INR %s.',$minWithdrawAmt),true);
				} else {
					$user	=	$this->Users->find()->where(['id'=>$decoded['user_id'],'Users.status'=>ACTIVE])->first();
					// $message	=	__('Under maintenance.',true);
					if(!empty($user)) {

						if( $user->first_name !='' && $user->address !='' ){

							$winningAmount	=	$user->winning_balance;
							if($winningAmount > 0 && $winningAmount >= $decoded['withdraw_amount'] ) {
								// update admin wallet start
								$adminWallet	=	$this->adminWallet();
								if(empty($adminWallet)) {
									$adminWallet	=	$this->AdminWallet->newEntity();
									$adminWallet->wallet_amount	=	$decoded['withdraw_amount'];
								} else {
									$adminWallet->wallet_amount	+=	$decoded['withdraw_amount'];
								}
								$this->AdminWallet->save($adminWallet);
								// update admin wallet end
								
								
								$remainingAmount	=	$winningAmount - $decoded['withdraw_amount'];
								$withdrawData	=	$this->WithdrawRequests->newEntity();
								
								$withdrawData->email			=	$user->email;
								$withdrawData->amount			=	$remainingAmount;
								$withdrawData->user_id			=	$decoded['user_id'];
								$withdrawData->refund_amount	=	$decoded['withdraw_amount'];
								$withdrawData->type				=	'Bank';//$decoded['type'];
								$withdrawData->request_status	=	0;
								$withdrawData->created			=	date('Y-m-d H:i:s');
								$withdrawData->modified			=	date('Y-m-d H:i:s');
								if($wres = $this->WithdrawRequests->save($withdrawData)) {
									$user->winning_balance	=	$remainingAmount;
									$this->Users->save($user);
									
									$txnId	=	'WR'.date('Ymd').time().$decoded['user_id'];
									$this->saveTransaction($decoded['user_id'],$txnId,TRANSACTION_PENDING,$decoded['withdraw_amount'],$wres->id);
								}
								$status		=	true;
								$message	=	__('Your withdraw request submitted succussfully.',true);
							} else {
								$message	=	__('The amount you have entered is more than your total available winnings for withdrawal, please enter a realistic amount',true);
							}
							
						} else {
							$message	=	__('Please update your Name, Address first then try again.',true);
						}

						
					} else {
						$message	=	__('Invalid user id.',true);
					}
				}
			} else {
				$message	=	__("match id or series id are empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function bankDetails() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Users');
		$this->loadModel('WithdrawRequests');
		if(!empty($decoded)) {
			if(!empty($decoded['user_id'])) {
				$user	=	$this->Users->find()->where(['Users.id'=>$decoded['user_id'],'Users.status'=>ACTIVE])->contain(['BankDetails'])->first();
				if(!empty($user)) {
					$winningBal	=	!empty($user->winning_balance) ? $user->winning_balance : 0;
					if(!empty($user->bank_detail)) {
						$bankName	=	$user->bank_detail->bank_name;
						$accountNo	=	$user->bank_detail->account_number;
					}
					$data1->winning_amount	=	$winningBal;
					$data1->bank_name		=	$bankName;
					$data1->account_no		=	$accountNo;
					$data1->min_withdraw_amount		=	Configure::read('Admin.setting.min_withdraw_amount');
					$status	=	true;
				} else {
					$message	=	__('Invalid user id.',true);
				}
			} else {
				$message	=	__("match id or series id are empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function entryPerTeam() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Users');
		$this->loadModel('WithdrawRequests');
		if(!empty($decoded)) {
			if(!empty($decoded['user_id']) && !empty($decoded['contest_size']) && !empty($decoded['winning_amount'])) {
				$authUser	=	$this->Users->find()->where(['id'=>$decoded['user_id'],'status'=>ACTIVE])->first();
				$commission	=	Configure::read('Admin.setting.contest_commission');
				if(!empty($authUser)) {
					$winningAmount	=	$decoded['winning_amount'];
					$contetSize		=	$decoded['contest_size'];
					$entryFee		=	0;
					if($winningAmount > 0) {
						$percent	=	($winningAmount/100) * $commission;
						$finalWinAmt=	$percent + $winningAmount;
						$entryFee	=	$finalWinAmt / $contetSize;
					}
					$status	=	true;
					$data1->entry_fee	=	(double) str_replace(',','',number_format($entryFee,2));
					$message=	__('Entry Fee',true);
				} else {
					$message	=	__('Invalid user id.',true);
				}
			} else {
				$message	=	__("match id or series id are empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function contestPrizeBreakup() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Users');
		$this->loadModel('UserContestBreakup');
		if(!empty($decoded)) {
			if(!empty($decoded['user_id']) && !empty($decoded['contest_size'])) {
				$authUser	=	$this->Users->find()->where(['id'=>$decoded['user_id'],'status'=>ACTIVE])->first();
				if(!empty($authUser)) {
					$breakup	=	$this->UserContestBreakup->find()->where(['contest_size_start <='=>$decoded['contest_size'],'contest_size_end >='=>$decoded['contest_size']])->group(['winner'])->order(['winner'=>'DESC'])->toArray();
					$prizeArray	=	[];
					if(!empty($breakup)) {
						foreach($breakup as $key=>$prizes) {
							$winnerPrice	=	$this->UserContestBreakup->find()->where(['contest_size_start <='=>$decoded['contest_size'], 'contest_size_end >='=>$decoded['contest_size'],'winner'=>$prizes->winner])->order(['winner'=>'DESC'])->toArray();
							$prizeArray[$key]['title']	=	$prizes->winner;
							if(!empty($winnerPrice)) {
								foreach($winnerPrice as $winnerKey =>$winnerValue) {
									$prizeArray[$key]['info'][$winnerKey]['rank_size']	=	$winnerValue->rank;
									$prizeArray[$key]['info'][$winnerKey]['percent']	=	$winnerValue->percent_prize;
								}
							}
						}
					}
					$data1	=	$prizeArray;
					$status	=	true;
				} else {
					$message	=	__('Invalid user id.',true);
				}
			} else {
				$message	=	__("match id or series id are empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function createContest() {
		echo die;
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('UserContestBreakup');
		$this->loadModel('PlayerTeamContests');
		$this->loadModel('Contest');
		$this->loadModel('SeriesSquad');
		$this->loadModel('Users');
		$this->loadModel('UserContestRewards');
		if(!empty($decoded)) {
			$commission	=	Configure::read('Admin.setting.contest_commission');
			if(!empty($decoded['user_id']) && !empty($decoded['contest_size']) && !empty($decoded['series_id']) && !empty($decoded['match_id']) && !empty($decoded['team_id']) && !empty($decoded['winners_count'])) {
				$authUser	=	$this->Users->find()->where(['id'=>$decoded['user_id'],'status'=>ACTIVE])->first();
				if(!empty($authUser)) {
					// team_id
					$contest	=	$this->Contest->newEntity();
					
					// create contest data
					$saveData['contest_size']	=	$decoded['contest_size'];
					$saveData['winning_amount']	=	$decoded['winning_amount'];
					$saveData['entry_fee']		=	$decoded['entry_fee'];
					$saveData['admin_comission']=	$commission;
					$saveData['contest_type']	=	($decoded['winning_amount'] > 0) ? 'Paid' : 'Free';
					$saveData['multiple_team']	=	(stripos($decoded['join_multiple'],'yes') !== false) ? 'yes' : '';
					$saveData['status']			=	ACTIVE;
					
					// contest that are created on series by user
					$saveData['user_contest']['user_id']		=	$decoded['user_id'];
					$saveData['user_contest']['series_id']		=	$decoded['series_id'];
					$saveData['user_contest']['match_id']		=	$decoded['match_id'];
					$saveData['user_contest']['contest_name']	=	$decoded['contest_name'];
					
					// contest price breakup
					if($decoded['winning_amount'] > 0) {
						$prizeBreakup	=	$this->UserContestBreakup->find()->where(['contest_size_start <='=>$decoded['contest_size'],'contest_size_end >='=>$decoded['contest_size'],'winner'=>$decoded['winners_count']])->toArray();
						$breakpArr	=	[];
						if(!empty($prizeBreakup)) {
							$saveData['price_breakup']		=	1;
							foreach($prizeBreakup as $key=> $breakup) {
								$winnigAmount	=	$decoded['winning_amount'];
								$percent		=	$breakup->percent_prize;
								$prizeMOney		=	($winnigAmount / 100) * $percent;
								
								$priceRange		=	explode(' - ',str_replace('Rank ','',$breakup->rank));
								$breakpArr[$key]['name']		=	$breakup->rank;
								$breakpArr[$key]['start']		=	isset($priceRange[0]) ? $priceRange[0] : 0;
								$breakpArr[$key]['end']			=	isset($priceRange[1]) ? $priceRange[1] : $priceRange[0];
								$breakpArr[$key]['percentage']	=	$breakup->percent_prize;
								$breakpArr[$key]['price']		=	$prizeMOney;
							}
						}
						$saveData['custom_breakup']	=	$breakpArr;
					}
					
					// create contest invite code
					$string	=	'0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
					$strShuffled	=	str_shuffle($string);
					$contestStr		=	substr($strShuffled,0,5);
					$inviteCode		=	'1Q'.$contestStr.substr(str_shuffle($strShuffled),0,5);
					// add match Contst
					$seriesMatch	=	$this->SeriesSquad->find()->where(['match_id'=>$decoded['match_id'],'series_id'=>$decoded['series_id']])->select(['id','match_id','series_id'])->first();
					
					if(!empty($seriesMatch)) {
						$saveData['match_contest'][0]['match_id']	=	$seriesMatch->id;
						$saveData['match_contest'][0]['invite_code']	=	$inviteCode;
						$saveData['match_contest'][0]['created']		=	date('Y-m-d H:i:s');
					}
					
					$this->Contest->patchEntity($contest,$saveData);
					if($result = $this->Contest->save($contest)) {
						$decoded['contest_id']	=	$result->id;
						if(!empty($result)) {
							// assign contest to series match
							$teamContest	=	$this->PlayerTeamContests->newEntity();
							$teamContest->player_team_id=	$decoded['team_id'];
							$teamContest->match_id		=	$decoded['match_id'];
							$teamContest->series_id		=	$decoded['series_id'];
							$teamContest->contest_id	=	$result->id;
							$teamContest->user_id		=	$decoded['user_id'];
							if($this->PlayerTeamContests->save($teamContest)) {
								$user	=	$this->Users->find()
											->select(['id','cash_balance','winning_balance','bonus_amount','ReferalCodeDetails.user_id','ReferalCodeDetails.refered_by','ReferalCodeDetails.refered_by_amount','ReferalCodeDetails.id'])
											->where(['Users.id'=>$decoded['user_id']])
											->contain(['ReferalCodeDetails'])->first();

								
								$usable_bonus_percentage=	0;	
								$contestType=	$result->contest_type;
								$usable_bonus_percentage=	$result->usable_bonus_percentage;
								$entryFee	=	!empty($result) ? $result->entry_fee : 0;
								if($contestType == 'Paid') {
									// create transation log for joining Contest
									$joinContestTxnId	=	'JL'.date('Ymd').time().$decoded['user_id'];
									$this->saveTransaction($decoded['user_id'],$joinContestTxnId,JOIN_CONTEST,$entryFee);
									
									/* $adminPer	=	Configure::read('Admin.setting.admin_percentage');
									if( $usable_bonus_percentage >0 ){
										$adminPer	=	$usable_bonus_percentage;
									} */

									$adminPer	=	$usable_bonus_percentage;
									$useAmount	=	($adminPer /100) * $entryFee;
									$saveData	=	[];
									if(!empty($user)) {
										$cashAmount	=	0;
										$winAmount	=	0;
										$bonusAmount=	0;
										if(!empty($user->bonus_amount) && $user->bonus_amount > 0) {
											if($useAmount <= $user->bonus_amount) {
												$remainingFee	=	$entryFee - $useAmount;
												$saveData['bonus_amount']	=	$user->bonus_amount - $useAmount;
												$bonusAmount	=	$useAmount;
												// $this->saveJoinContestDetail($decoded,$useAmount,CASH_BONUS);
											} else {
												$remainingFee	=	$entryFee - $user->bonus_amount;
												$saveData['bonus_amount']	=	0;
												$bonusAmount	=	$user->bonus_amount;
											}
										} else {
											$saveData['bonus_amount']	=	0;
											$remainingFee	=	$entryFee;
										}
										
										if(!empty($remainingFee)) {
											$cashBalance=	$user->cash_balance;
											if(!empty($cashBalance)) {
												$cashBal		=	($cashBalance > $remainingFee) ? $cashBalance - $remainingFee : 0;
												$remainingFee	=	($cashBalance < $remainingFee) ? $remainingFee - $cashBalance : 0;
												$saveData['cash_balance']	=	$cashBal;
												$cashAmount		=	($cashBalance > $remainingFee) ? $remainingFee : $cashBalance;
												// $this->saveJoinContestDetail($decoded,$cashAmount,WINNING_CASH);
											}
										}
										if(!empty($remainingFee)) {
											$winningBal	=	$user->winning_balance;
											if(!empty($winningBal)) {
												$winningBal1	=	($winningBal > $remainingFee) ? $winningBal - $remainingFee : 0;
												$remainingFee	=	($winningBal < $remainingFee) ? $remainingFee - $winningBal : 0;
												$saveData['winning_balance']	=	$winningBal1;
												$winAmount	=	($winningBal > $remainingFee) ? $remainingFee : $winningBal;
												// $this->saveJoinContestDetail($decoded,$winAmount,WINNING_CASH);
											}
										}
										$this->saveJoinContestDetail($decoded,$bonusAmount,$winAmount,$cashAmount);
										
									}
									
									// add reward if 20 contest are completed Start
									$usersContest	=	$this->PlayerTeamContests->find()->where(['user_id'=>$decoded['user_id'],'Contest.contest_type LIKE'=>'Paid'])->contain(['Contest'])->count();
									if($usersContest % 20 == 0) {
										$saveReward['user_id']	=	$decoded['user_id'];
										$saveReward['reward']	=	20;
										$saveReward['date']		=	date('Y-m-d');
										$userRewards	=	$this->UserContestRewards->newEntity();
										$this->UserContestRewards->patchEntity($userRewards,$saveReward);
										if($this->UserContestRewards->save($userRewards)) {
											$saveData['bonus_amount']	=	$saveData['bonus_amount'] + 20;
											$txnId	=	'CB'.date('Ymd').time().$decoded['user_id'];
											$this->saveTransaction($decoded['user_id'],$txnId,LEVEL_UP,20);
										}
									}
									// add reward if 20 contest are completed End

									

									if(!empty($saveData)) {
										$this->Users->patchEntity($user,$saveData);
										$this->Users->save($user);
									}
								}

								$message	=	__('You cantest created successfully.',true);
								$status		=	true;
								$data1->invite_code	=	$inviteCode;
								
							}
						}
					}
				} else {
					$message	=	__('Invalid user id.',true);
				}
			} else {
				$message	=	__("match id or series id are empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function seriesPlayerDetail() {
		error_reporting(0);
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Users');
		$this->loadModel('SeriesPlayers');
		$this->loadModel('LiveScore');
		$this->loadModel('SeriesSquad');
		$this->loadModel('PlayerTeams');
		$this->loadModel('PlayerTeamDetails');
		if(!empty($decoded)) {
			$commission	=	Configure::read('Admin.setting.contest_commission');
			if(!empty($decoded['user_id']) && !empty($decoded['series_id'])  && !empty($decoded['player_id'])) {
				$user	=	$this->Users->find()->where(['id'=>$decoded['user_id'],'status'=>ACTIVE])->first();
				if(!empty($user)) {
					$playerDetail	=	$this->SeriesPlayers->find()->where(['SeriesPlayers.player_id'=>$decoded['player_id'],'series_id'=>$decoded['series_id']])->contain(['PlayerRecord'])->first();
					$playerName	=	$playerImage	=	$bowlsType	=	$batsType	=	$nationality	=	$DOB	=	'';
					if(!empty($playerDetail)) {
						$playerName	=	!empty($playerDetail->player_record) ? $playerDetail->player_record->player_name : $playerDetail->player_name;
						$image		=	!empty($playerDetail->player_record) ? $playerDetail->player_record->image : '';
						$rootPath	=	WWW_ROOT.'uploads'.DS.'player_image'.DS;
						if(!empty($image) && file_exists($rootPath.$image)) {
							$playerImage	=	SITE_URL.'uploads/player_image/'.$image;
						}
						$batsType	=	!empty($playerDetail->player_record) ? $playerDetail->player_record->batting_style : '';
						$bowlsType	=	!empty($playerDetail->player_record) ? $playerDetail->player_record->bowling_style : '';
						$nationality=	!empty($playerDetail->player_record) ? $playerDetail->player_record->country : '';
						$birthday	=	!empty($playerDetail->player_record) ? $playerDetail->player_record->born : '';
						if(!empty($birthday)) {
							$explodeDob	=	explode(',',$birthday);
							$DOB		=	date('M d',strtotime($explodeDob[0])).','.$explodeDob[1];
						}
					}
					// player total points in
					$playerPoints	=	$this->LiveScore->find('list', ['keyField'=>'id','valueField'=>'point'])->where(['seriesId'=>$decoded['series_id'],'playerId'=>$decoded['player_id']])->toArray();
					
					$playerTotalPoints	=	!empty($playerPoints) ? array_sum($playerPoints) : 0;
					
					// series matched detail
					$completeDate	=	date('Y-m-d',strtotime('-1 week'));
					$currentDate	=	date('Y-m-d');
					$playerMatches	=	$this->SeriesSquad->find()->where([/*'date >=' => $completeDate,'date <=' => $currentDate,'match_status'=>MATCH_FINISH,*/'Series.status'=>ACTIVE,'SeriesSquad.status'=>ACTIVE,'SeriesSquad.series_id'=>$decoded['series_id']])->group('SeriesSquad.match_id')->contain(['Series', 'LocalMstTeams','VisitorMstTeams'])->order(['date','time'])->toArray();
					
					$matchDetail	=	[];
					if(!empty($playerMatches)) {
						$flag	=	0;
						foreach($playerMatches as $matchKey => $matchValue) {
							
							$liveScore	=	$this->LiveScore->find()->where(['seriesId'=>$decoded['series_id'],'matchId'=>$matchValue->match_id,'playerId'=>$decoded['player_id']])->first();
							$points		=	0;
							$selectedBy	=	0;
							if(!empty($liveScore)) {
								$points	=	$liveScore->point;
								// player selected in teams
								$teamData	=	$this->PlayerTeams->find()->where(['series_id'=>$decoded['series_id'],'match_id'=>$matchValue->match_id])->count();
								$teamPlayer	=	$this->PlayerTeamDetails->find()->where(['player_id'=>$decoded['player_id'],'PlayerTeams.series_id'=>$decoded['series_id'],'PlayerTeams.match_id'=>$matchValue->match_id])->contain(['PlayerTeams'])->count();
								if(!empty($teamData) && !empty($teamPlayer)) {
									$selectedBy	=	($teamPlayer/$teamData) * 100;
								}
								$visitorTeam=	$matchValue->visitor_mst_team;
								$localTeam	=	$matchValue->local_mst_team;
								
								$localTeamName	=	!empty($localTeam->team_short_name) ? $localTeam->team_short_name : $localTeam->team_name;
								$visitorTeamName=	!empty($visitorTeam->team_short_name) ? $visitorTeam->team_short_name : $visitorTeam->team_name;
								$matchDetail[$flag]['Match']		=	$visitorTeamName.' vs '.$localTeamName;
								$matchDetail[$flag]['date']			=	date('M d, Y',strtotime($matchValue->date));
								$matchDetail[$flag]['player_points']=	(double) $points;
								$matchDetail[$flag]['selected_by']	=	number_format($selectedBy).'%';
								$flag++;
							}
						}
					}
					
					$data1->player_name			=	$playerName;
					$data1->player_image		=	$playerImage;
					$data1->player_credit		=	$playerDetail->player_record->player_credit;
					$data1->bats_type			=	$batsType;
					$data1->bowls_type			=	$bowlsType;
					$data1->nationality			=	$nationality;
					$data1->birthday			=	$DOB;
					$data1->player_total_points	=	$playerTotalPoints;
					$data1->match_detail		=	$matchDetail;
					$status		=	true;
				} else {
					$message	=	__('Invalid user id.',true);
				}
			} else {
				$message	=	__("match id or series id are empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function deleteNotifications() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Users');
		$this->loadModel('Notifications');
		if(!empty($decoded)) {
			if(!empty($decoded['user_id'])) {
				$user	=	$this->Users->find()->where(['id'=>$decoded['user_id'],'status'=>ACTIVE])->first();
				if(!empty($user)) {
					$notificationId	=	$decoded['notification_id'];
					$filter	=	'';
					if(!empty($notificationId)) {
						$filter	=	['id'=>$notificationId];
					}
					$notification	=	$this->Notifications->find()->where(['user_id'=>$decoded['user_id'],$filter])->toArray();
					if(!empty($notification)) {
						foreach($notification as $notice) {
							$this->Notifications->delete($notice);
						}
						$message	=	__('Notifications deleted successfully.',true);
						$status		=	true;
					} else {
						$message	=	__('Notifications not found.',true);
					}
				} else {
					$message	=	__('Invalid user id.',true);
				}
			} else {
				$message	=	__("match id or series id are empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function beforJoinContest() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('PointSystem');
		$this->loadModel('SeriesSquad');
		if(!empty($decoded)) {
			if(!empty($decoded['series_id']) && !empty($decoded['match_id'])) {
				$seriesMatch	=	$this->SeriesSquad->find()->where(['series_id'=>$decoded['series_id'],'match_id'=>$decoded['match_id']])->first();
				if(!empty($seriesMatch)) {
					$matchType	=	$seriesMatch->type;
					if(($matchType=='Test') || ($matchType=='First-class')){
						$pointSystem	=	$this->PointSystem->find()->where(['matchType'=>'3'])
											->select(['battingRun','fieldingCatch','bowlingWicket'])
											->first();
					}elseif ($matchType=='ODI') {
						$pointSystem	=	$this->PointSystem->find()->where(['matchType'=>'2'])
											->select(['battingRun','fieldingCatch','bowlingWicket'])
											->first();
					}elseif ($matchType=='T20') {
						$pointSystem	=	$this->PointSystem->find()->where(['matchType'=>'1'])
											->select(['battingRun','fieldingCatch','bowlingWicket'])
											->first();
					}elseif ($matchType=='T10') {
						$pointSystem	=	$this->PointSystem->find()->where(['matchType'=>'4'])
											->select(['battingRun','fieldingCatch','bowlingWicket'])
											->first();
					}
					
					$data1->run		=	!empty($pointSystem) ? $pointSystem->battingRun : 0;
					$data1->catch	=	!empty($pointSystem) ? $pointSystem->fieldingCatch : 0;
					$data1->wicket	=	!empty($pointSystem) ? $pointSystem->bowlingWicket : 0;
					$status			=	true;
				}
			} else {
				$message	=	__("match id or series id are empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		// die;
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
	public function callBackDepositAmount() {
		$data	=	$_REQUEST;

		$this->log("Response payment request callBackDepositAmount: - ".print_r($data, true), 'debug');

		$this->loadModel('DepositAmountLogs');
		$this->loadModel('Transactions');
		$entity	=	$this->DepositAmountLogs->newEntity();
		$entity->created	=	date('Y-m-d H;i:s');
		$entity->status		=	ACTIVE;
		$entity->content	=	serialize($data);
		$this->DepositAmountLogs->save($entity);
		$unData	=	unserialize('a:8:{s:7:"orderId";s:5:"10123";s:11:"orderAmount";s:4:"1.00";s:11:"referenceId";s:8:"16620449";s:8:"txStatus";s:7:"SUCCESS";s:11:"paymentMode";s:3:"UPI";s:5:"txMsg";s:22:"Transaction Successful";s:6:"txTime";s:19:"2019-03-20 15:04:17";s:9:"signature";s:44:"qBkbjpDVJw522UXu9P7CXKUMkAiDBNMm7S4hsUfLtb8=";}');
		
		if(!empty($data)) {
			if(isset($data['txStatus']) && $data['txStatus'] == 'SUCCESS' && isset($data['orderId']) && $data['orderId'] != '' && isset($data['orderAmount']) && $data['orderAmount'] != '' && isset($data['referenceId']) && $data['referenceId'] != '') {
				$txnEntity	=	$this->Transactions->find()->where(['order_id'=>$data['orderId'],'txn_id'=>$data['referenceId']])->first();
				if(!empty($txnEntity)) {
					$txnEntity->txn_id		=	$data['referenceId'];
					$txnEntity->order_id	=	$data['orderId'];
					// $txnEntity->banktxn_id	=	$decoded['banktxn_id'];
					$txnEntity->txn_amount	=	$data['orderAmount'];
					$txnEntity->status		=	1;
					if($trResult = $this->Transactions->save($txnEntity)) {
						$users	=	$this->Users->find()->where(['id'=>$txnEntity->user_id,'Users.status'=>ACTIVE])->first();
						$users->cash_balance	+=	$data['orderAmount'];
						$this->Users->save($users);


						//Send confirmation email
						$this->loadModel('EmailTemplates');
						$template	=	$this->EmailTemplates->find()->where(['subject'=>'amount_deposit','status'=>ACTIVE])->first();
						if(!empty($template)) {
							$to			=	$users->email;
							$from		=	Configure::read('Admin.setting.admin_email');
							$subject	=	$template->email_name;
							$message1	=	str_replace(['{{deposit_amount}}'],[$data['orderAmount']],$template->template);
							$this->sendMail($to, $subject, $message1, $from);
						}


					}
				}
			}
		}
		die;
	}
	
	public function createTransactionId() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		$this->loadModel('Transactions');
		// $this->loadModel('SeriesSquad');
		if(!empty($decoded)) {
			if(!empty($decoded['order_id']) && !empty($decoded['user_id']) && !empty($decoded['txn_amount'])) {
				$txnEntity	=	$this->Transactions->newEntity();
				$txnEntity->user_id		=	$decoded['user_id'];
				$txnEntity->order_id	=	$decoded['order_id'];
				$txnEntity->txn_amount	=	$decoded['txn_amount'];
				$txnEntity->currency	=	'INR';
				$txnEntity->gateway_name=	'CASH_FREE';
				$txnEntity->txn_date	=	date('Y-m-d H:i:s');
				$txnEntity->created		=	date('Y-m-d H:i:s');
				// $txnEntity->checksum	=	$decoded['checksum'];
				$txnEntity->local_txn_id=	'DD'.date('Ymd').time().$decoded['user_id'];
				$txnEntity->added_type	=	CASH_DEPOSIT; // Deposit Cash status
				$txnEntity->status		=	0; // status
				$this->Transactions->save($txnEntity);
				$status	=	true;
			} else {
				$message	=	__("Please check all details.", true);
			}
			
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		// die;
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}

	public function getIfscInfo() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);

		if(!empty($decoded)) {
			if( !empty($decoded['ifsccode']) ) {
				
				$endpoint = "https://ifsc.razorpay.com/".$decoded['ifsccode'];
				$headers = []; 
				
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $endpoint);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				$returnData = curl_exec($ch);
				curl_close($ch);
				if ( $returnData != "" && $returnData != '"Not Found"' ) {
					$data1  = 	json_decode($returnData);
					$status	=	true;	
					$message	=	__("Bank Details fetched successfully.", true);
				} else {
					$status	=	false;	
					$message	=	__("Bank Details not fetched.", true);
				}
				
			} else {
				$message	=	__("Please check ifsc code.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}


	public function getsponsorcode() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);
		
		//Check referred by
		$this->loadModel('Invites');
		$referedBySponsor = '';
		$referedBySide = '';
		$ip = $_SERVER['REMOTE_ADDR'];
		$refered	=	$this->Invites->find()->select(['id','refer_id','side'])->where(['Invites.ip'=>$ip])->first();
		if(!empty($refered)){
			$status		=	true;
			$referedBySponsor = $refered->refer_id;
			$referedBySide = $refered->side;
			$message	=	__("", true);
		} else {
			$message	=	__("", true);
		}

		$response_data	=	array('status'=>$status,'tokenexpire'=>0,'message'=>$message,'data'=>$referedBySponsor);
		echo json_encode(array('response' => $response_data));
		die;
	}

	public function getsettings() {
		$status		=	false;
		$message	=	NULL;
		$data		=	[];
		$data1		=	(object) array();
		$data_row	=	file_get_contents("php://input");
		$decoded    =	json_decode($data_row, true);

		if(!empty($decoded)) {
			if( !empty($decoded['user_id']) ) {

				$totalBalance	=	$cashBalance	=	$winngsAmount	=	$bonus	=	0;
				$this->loadModel('Users');
				$users	=	$this->Users->find()->where(['Users.id'=>$decoded['user_id'],'Users.status'=>ACTIVE])->first();

				if(!empty($users)) {
					$cashBalance	=	$users->cash_balance;
					$winngsAmount	=	$users->winning_balance;
					$bonus			=	$users->bonus_amount;
					$totalBalance	=	$cashBalance + $winngsAmount + $bonus;
				}
				
				$data1->totalBalance 	= 	round($totalBalance,2);
				$data1->version_code	=	VERSION_CODE;
				$data1->apk_url			=	SITE_URL.DOWNLOAD_APK_NAME;
				$data1->update_type		=	1; //1 
				$data1->update_text		=	'';

				//Update app version
				if($decoded['user_id']){
					$app_version = (isset($decoded['app_version'])) ? $decoded['app_version'] : 0;
					$usersTable = TableRegistry::get('Users');
					$usersTableQuery = $usersTable->query();
					$return = $usersTableQuery->update()
						->set(['app_version' => $app_version])
						->where(['id' => $decoded['user_id']])
						->execute();
				}
				

				
			} else {
				$message	=	__("User id is empty.", true);
			}
		} else {
			$message	=	__("You are not authenticated user.", true);
		}
		
		$response_data	=	array('status'=>$status,'message'=>$message,'data'=>$data1);
		echo json_encode(array('response' => $response_data));
		die;
	}
	
}