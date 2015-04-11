<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CreateDemoData extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'batch:createDemoData';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Command description.';

  private $activity;
  private $activity_category;
  private $activity_category_group;

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
  {
    $this->activity = App::make('Activity');
    $this->activity_category = App::make('ActivityCategory');
    $this->activity_category_group = App::make('ActivityCategoryGroup');
    $this->user_service = App::make('UserService');

    parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
  {
    $user_id = 0;

    // 既存データの削除
    $this->comment('deleting sample data');

    $builder = $this->activity->where('user_id', '=', $user_id)
      ->forceDelete();
    $builder = $this->activity_category->where('user_id', '=', $user_id);

    foreach ($builder->get() as $activity_category) {
      $this->activity_category_group->where('activity_category_id', '=', $activity_category->id)
        ->forceDelete();
      $this->activity_category->where('id', '=', $activity_category->id)
        ->forceDelete();
    }

    $this->comment('deleted sample data');

    // データの作成
    $this->comment('creating sample data');
    $activity_category_group_ids = $this->user_service->setup($user_id);

    // activitiesレコードの登録
    $calc_date = new DateTime();
    $calc_date->sub(new DateInterval('P3Y'));
    $current_date = new DateTime();

    $k = 1;
    $constants = array();

    while ($calc_date->getTimestamp() <= $current_date->getTimestamp()) {
      $j = mt_rand(0, 2);

      for ($i = 0; $i < $j; $i++) {
        $k++;

        $index = mt_rand(0, sizeof($activity_category_group_ids) - 1);
        $amount = 0;

        if ($activity_category_group_ids[$index]['balance_type'] == ActivityCategory::BALANCE_TYPE_EXPENSE) {
          $amount = mt_rand(-10000, 0);
        } else {
          $amount = mt_rand(0, 10000);
        }

        $activity_date = $calc_date->format('Y-m-d');
        $insert_constant = false;

        // 固定収支レコードが月ごとに複数登録されないよう対策
        if ($activity_category_group_ids[$index]['cost_type'] == ActivityCategory::COST_TYPE_CONSTANT) {
          $target_month = $calc_date->format('Y-m');

          if (!isset($constants[$target_month][$activity_category_group_ids[$index]['id']])) {
            $insert_constant = true;
            $constants[$target_month][$activity_category_group_ids[$index]['id']] = true;
          }
        }

        if ($activity_category_group_ids[$index]['cost_type'] == ActivityCategory::COST_TYPE_VARIABLE || $insert_constant) {
          $model = new $this->activity();
          $model->user_id = $user_id;
          $model->activity_date = $activity_date;
          $model->activity_category_group_id = $activity_category_group_ids[$index]['id'];
          $model->location = 'サンプル' . mt_rand(1, 10);
          $model->content = 'サンプル';
          $model->amount = $amount;
          $model->credit_flag = mt_rand(0, 1);
          $model->special_flag = mt_rand(0, 1);
          $model->save();
        }
      }

      $calc_date->add(new DateInterval('P1D'));
    }

    $this->comment('created sample data');
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
  {
    return array();
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
  {
    return array();
	}

}
