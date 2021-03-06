<template>
    <div class="section">
        <div class="container">
            <!-- $t is vue-i18n global function to translate lang -->
            <div class="app-container">
                <template v-if="classes.length === 0">
                    <el-card v-for="i in [1,2,3,4]" v-bind:key="i" class="box-card class-box empty-box"></el-card>
                </template>
                <template v-else>
                    <el-card v-for="_class in classes" v-bind:key="_class.id" class="box-card class-box">
                        <h1 class="title"> {{_class.name}}</h1>
                        <el-row :gutter="20" v-for="(row,index) in group(_class.quizAccess)" v-bind:key="`quiz-${index}`">
                            <el-col :span="6"  v-for="access in row" v-bind:key="access.id">
                                <el-card class="box-card exam-card">
                                    <div slot="header" class="clearfix">
                                        <span> {{ access.quiz.name }}</span>
                                    </div>
                                    {{access.quiz.description}}
                                    <div class="small-text">
                                        {{formattedTime(access.openDate)}} -- {{formattedTime(access.closeDate)}}
                                    </div>
                                    <el-divider></el-divider>
                                    <el-row :gutter="20">
                                        <el-col :span="12">
                                            {{ (access.quiz['@id'] in quizByAccess ? quizByAccess[access.quiz['@id']].userAttempts : 0 )}} / {{ access.numAttempts }}
                                        </el-col>
                                        <el-col :span="12" >
                                            <template v-if="isLessThenNow(access.openDate)">
                                                Opens {{ timestamp(access.openDate) }}
                                            </template>
                                            <template v-else-if="isLessThenNow(access.closeDate)">
                                                Closes {{ timestamp(access.closeDate) }}
                                            </template>
                                            <template v-else>
                                                Closed {{ timestamp(access.closeDate) }}
                                            </template>
                                            </el-col>
                                    </el-row>

                                    <div class="foot">
                                        <template v-if="access.isOpen">
                                            <router-link :to="{name: 'app.exam.start', params: {quiz_access_id: access.id} }">
                                                <el-button type="primary" style="width: 100%">Start</el-button>
                                            </router-link>
                                        </template>
                                        <template v-else>
                                            <el-button type="primary" style="width: 100%" disabled>Locked</el-button>
                                        </template>
                                    </div>
                                </el-card>
                            </el-col>
                        </el-row>
                    </el-card>
                </template>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
import _ from 'lodash';
import NProgress from 'nprogress';
import {Component, Provide, Vue} from "vue-property-decorator";
import {namespace} from "vuex-class";
import User from "../../entity/user";
import service from "../../utils/request";
import Classroom from "../../entity/classroom";
import {HydraCollection} from "../../entity/hydra";
import {FilterBuilder, SearchFilter} from "../../utils/filter";
import UserQuizAccess from "../../entity/user-quiz-access";
import QuizAccess from "../../entity/quiz-access";
import { DateTime } from 'luxon';
import * as moment from 'moment';

const authModule = namespace('auth')

@Component
export default class Home extends Vue {
    @authModule.Getter("user") user: User;
    @Provide() classes: Classroom[] = [];
    @Provide() access: UserQuizAccess[] = [];

    getUserAccess(query:string){
        service({
            url: query,
            method: 'GET'
        }).then((response) => {
            const collection: HydraCollection<UserQuizAccess> = response.data;
            this.access = this.access.concat(collection["hydra:member"]);
            if(collection["hydra:view"]["hydra:next"]){
                this.getUserAccess(collection["hydra:view"]["hydra:next"]);
            }

        }).catch((err) => {

        });
    }

    get quizByAccess() {
        return _.keyBy(this.access,(e) => e.quiz);
    }

    created() {
        NProgress.start();

        service({
            url: '_api/classrooms/user/' + this.user.id,
            method: 'GET'
        }).then((response) => {
            const collection: HydraCollection<Classroom> =  response.data;
            this.classes = this.classes.concat(collection["hydra:member"]);
            this.classes.forEach((c : Classroom) => {
                c.quizAccess = _.filter(<QuizAccess[]>c.quizAccess,(a:QuizAccess) => {
                    return !a.isHidden;
                });

            });

            NProgress.done();
        }).catch((err) => {
            console.log(err);
        });
        const accessBuilder = new FilterBuilder();
        service({
            url: '_api/user_quiz_accesses?' + (new FilterBuilder()).addFilter(new SearchFilter('owner',this.user.id+'')).build(),
            method: 'GET'
        }).then((response) => {
            const collection: HydraCollection<UserQuizAccess> = response.data;
            this.access = collection["hydra:member"];
        }).catch((err) => {
        });
    }

    group(access: any) {
        return _.chunk(access, 4);
    };

    formattedTime(dateTime: any) {
        return moment(dateTime).format("d/M/Y, h:mm:ss a");
    }
    isLessThenNow(dateTime: any){
        return  moment(dateTime).isAfter(moment.now())
    }

    timestamp(dateTime: any) {
        return moment(dateTime).fromNow();
    };
};
</script>

<style rel="stylesheet/scss" lang="scss">
   .class-box{
       margin-bottom: 1rem;

       .title{
           font-size: 1.2rem;
       }
       .exam-card{
           margin-bottom: .4rem;
       }
       .foot{
           margin-top: .5rem;
       }
       &.empty-box{
        height: 40rem;
       }
       .small-text{
           font-size: .6rem;
       }
   }
</style>
