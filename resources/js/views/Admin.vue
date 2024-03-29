<template>
  <div class="container">
    <AppHeader></AppHeader>
    <div class="outer-controll-container center">
      <div class="controll-container">
        <div class="button-list space-between">
          <button v-on:click="reset" class="button">リセット</button>
          <button v-on:click="logout" class="button">ログアウト</button>
          <button v-on:click="shopClose" class="button">営業終了</button>
          <button v-on:click="openReadmePage" class="button">ヘルプ</button>
        </div>
        <button v-on:click="openForStoreDisplayPage" class="wide-button">店舗用画面表示</button>
        <div class="cut-status border-radius bg-white center">{{shopStatus}}</div>
        <Draggable v-model="cutNowNoList" group="cutNo" class="cut-number border-radius bg-white center">
          <div class="internal-cut-number">
            {{cutNow}}
          </div>
        </Draggable>
        <button v-on:click="issueWaitingNo" class="wide-button">受付番号発行</button>
        <div id="outer-number-list" class="space-between">
          <div class="number-list-box center-column">
            <div class="number-list-label center">カット済み</div>
            <div class="number-list-group border-solid">
              <Draggable v-model="cutDoneNoList" group="cutNo" class="number-list center-column">
                <div v-for="item in cutDoneNoList" :key="item.id" class="list-object list-object-margin border-radius bg-white center">
                  {{ item }}
                </div>
              </Draggable>
            </div>
          </div>
          <div class="number-list-box center-column">
            <div class="number-list-label center">待ち番号</div>
            <div class="number-list-group border-solid">
              <Draggable v-model="cutWaitNoList" group="cutNo" class="number-list center-column">
                <div v-for="item in cutWaitNoList" :key="item.id" class="list-object list-object-margin border-radius bg-white center">
                  {{ item }}
                </div>
              </Draggable>
            </div>
          </div>
          <div class="number-list-box center-column">
            <div class="number-list-label center">呼び出し中</div>
            <div class="number-list-group border-solid">
              <Draggable v-model="cutCallNoList" group="cutNo" class="number-list center-column">
                <div v-for="item in cutCallNoList" :key="item.id" class="list-object list-object-margin border-radius bg-white center">
                  {{ item }}
                </div>
              </Draggable>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import header from '../components/Header.vue'
import draggable from 'vuedraggable'
import axios from 'axios'
export default {
  components: {
    AppHeader: header,
    Draggable: draggable
  },
  data() {
    return {
      shopStatus: '',
      masterKey: '',
      cutWaitNoList: [],
      cutDoneNoList: [],
      cutCallNoList: [],
      cutNowNoList: [],
      updateWaitingNoState: [],
      created: false
    }
  },
  created: async function() {
    await this.callApiGetWaitNumber()
    this.updateLocalWaitingNo()
    if (this.$store.getters['cutNowNo'] !== '-') this.cutNowNoList.push(this.$store.getters['cutNowNo'])
    await this.callAPIGetShopStatus()
    this.shopStatus = this.printShopStatus(this.$store.getters['shopStatus'])
    this.masterKey = this.$store.getters['masterKey']
    this.created = true
  },
  asyncComputed: {
    async cutNow() {
      if (!this.created) return '';
      if (this.cutNowNoList.length > 1) {
        this.cutDoneNoList.push(this.cutNowNoList.shift())
        if (this.cutDoneNoList.indexOf('-') !== -1) {
          this.removeEmptyNumber()
          await this.updateShopStatus(2)
        }
      }else if (this.cutNowNoList.length === 0) {
        this.cutNowNoList.push('-')
        await this.callAPIGetShopStatus()
        if (this.$store.getters['shopStatus'] != 3) await this.updateShopStatus(3)
        this.removeEmptyNumber()
      }
      this.sortCutWait()
      this.sortCutDone()
      this.sortCutCall()
      this.updateOuterNoListHeight()
      this.$store.dispatch('commitUpdateAdminWaitingNoState', {
        updateObject: {
          waitNoList: this.cutWaitNoList,
          doneNoList: this.cutDoneNoList,
          callNoList: this.cutCallNoList,
          nowNoList: this.cutNowNoList,
        }
      })
      this.updateWaitingNoState = this.$store.getters['updateWaitingNoState']
      return this.cutNowNoList[0]
    }
  },
  watch: {
    updateWaitingNoState: async function() {
      if (this.updateWaitingNoState.length > 0) {
        await this.callAPIUpdateWaitingNoState()
        this.$store.dispatch('commitResetUpdateFlg')
      }
    },
  },
  methods: {
    removeEmptyNumber() {
      this.cutDoneNoList.indexOf('-') !== -1 ?
        this.cutDoneNoList.splice(this.cutDoneNoList.indexOf('-'), 1) : ''
      this.cutWaitNoList.indexOf('-') !== -1 ?
        this.cutWaitNoList.splice(this.cutWaitNoList.indexOf('-'), 1) : ''
      this.cutCallNoList.indexOf('-') !== -1 ?
        this.cutCallNoList.splice(this.cutCallNoList.indexOf('-'), 1) : ''
    },
    async updateShopStatus(shopStatusID) {
      await this.callAPIUpdateShopStatus(shopStatusID)
      this.shopStatus = this.printShopStatus(this.$store.getters['shopStatus'])
    },
    async issueWaitingNo() {
      await this.callAPIIssueWaitNumber()
      this.updateLocalWaitingNo()
    },
    async reset() {
      this.$awn.confirm(
        '順番待ち番号をリセットしますか？',
        async () => {
          if (await this.callAPIWaitNumberReset() == 0) {
            this.updateLocalWaitingNo()
            this.cutNowNoList[0] = this.$store.getters['cutNowNo']
            if (this.cutNowNoList[0] === '-') await this.updateShopStatus(3)
            this.$awn.success('順番待ち番号をリセットしました.')
            console.log('info:The Wait Number State was reseteds.')
          }
        },
        () => {})
    },
    sortCutDone() {
      this.cutDoneNoList.sort((a,b) => a < b ? 1 : -1)
    },
    sortCutWait() {
      this.cutWaitNoList.sort((a,b) => a > b ? 1 : -1)
    },
    sortCutCall() {
      this.cutCallNoList.sort((a,b) => a > b ? 1 : -1)
    },
    shopClose() {
      this.$awn.confirm(
        '<center>営業を終了しますか？<br>(この操作を実行すると順番待ち番号もリセットされます.)</center>',
        async () => {
          if (await this.callAPIWaitNumberReset() == 0) {
            this.updateLocalWaitingNo()
            this.cutNowNoList[0] = this.$store.getters['cutNowNo']
            if (this.cutNowNoList[0] === '-') await this.updateShopStatus(1)
            this.$awn.success('営業を終了しました.')
            console.log('info:The Shop was closed.')
          }
        },
        () => {}
      )
    },
    sessionOut() {
      let credential = {isLogin: false, accessToken: ''}
      this.$store.dispatch('commitUpdateLoginCredential', {credential})
      this.$router.push('login')
      console.log('info:The Login Token has not found in api server.')
    },
    async logout() {
      if (await this.callAPILogout() == 0) {
        this.$router.push('login')
        this.$awn.info('ログアウトしました.')
        console.log('info:System logout.')
      }
    },
    updateLocalWaitingNo() {
      this.cutWaitNoList = this.$store.getters['cutWaitNoList']
      this.cutDoneNoList = this.$store.getters['cutDoneNoList']
      this.cutCallNoList = this.$store.getters['cutCallNoList']
    },
    updateOuterNoListHeight() {
      let numberListLabels = document.getElementsByClassName('number-list-label')
      let numberListObjects = document.getElementsByClassName('list-object')
      let numberListLabelHeight = numberListLabels.length > 0 ? numberListLabels[0].clientHeight : 45
      let numberListObjectHeight =  numberListObjects.length > 0 ? numberListObjects[0].clientHeight : 42
      
      let maxObjectLength = this.cutWaitNoList.length
      maxObjectLength = this.cutDoneNoList.length > maxObjectLength ? this.cutDoneNoList.length : maxObjectLength
      maxObjectLength = this.cutCallNoList.length > maxObjectLength ? this.cutCallNoList.length : maxObjectLength
      let outerNoList = document.getElementById('outer-number-list')
      if (outerNoList !== null) {
        outerNoList.style.height = numberListLabelHeight + ((numberListObjectHeight + 5) * maxObjectLength) + 30 + 'px'
      }
    },
    printShopStatus(statusID) {
      switch (statusID) {
        case 1:
          return '営業終了'
        case 2:
          return 'カット中'
        case 3:
          return '準備中'
        default:
          return '-'
      }
    },
    openReadmePage() {
      window.open('/readme/', '_blank')
    },
    openForStoreDisplayPage() {
      window.open('/app/display/', '_blank')
    },
    apiErrorCode(axiosErrorMessage, errorMessages) {
      let errorCode = 0
      if (axiosErrorMessage !== undefined) {
        if (axiosErrorMessage.indexOf('Network Error') != -1) {
          errorCode = 1
        } else if(axiosErrorMessage.indexOf('status code 401') != -1){
          errorCode = 2
          this.sessionOut()
        } else {
          errorCode = 3
        }
        if (errorMessages !== undefined) {
          let errorMessage = errorMessages.filter(item => item.errorCode == errorCode)[0].errorMessage
          errorMessage !== undefined ? this.$awn.alert(errorMessage) : ''
        }
      }
      return errorCode
    },
    async callAPIUpdateShopStatus(statusID) {
      let axiosErrorMessage
      let errorMessages = [
        {errorCode: 1, errorMessage: '店状態の変更に失敗しました. ネットワーク接続を確認して下さい.'},
        {errorCode: 2, errorMessage: '店状態の変更に失敗しました. 再ログインして下さい.'},
        {errorCode: 3, errorMessage: '店状態の変更に失敗しました.'}
      ]
      const result = await axios.patch(
        '/api/v1/status', {
          'status_id': statusID
        }, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('AccessToken')}`
          }
        }).catch(
          (error) => {
            axiosErrorMessage = error.message
            console.log(error)
          }
        )
      if (result !== undefined) {
        this.$store.dispatch('commitUpdateShopStatus', {shopStatusID: statusID})
      }
      return this.apiErrorCode(axiosErrorMessage, errorMessages)
    },
    async callAPIGetShopStatus() {
      let axiosErrorMessage
      let errorMessages = [
        {errorCode: 1, errorMessage: '店状態の取得に失敗しました. ネットワーク接続を確認して下さい.'},
        {errorCode: 2, errorMessage: '店状態の取得に失敗しました. 再ログインして下さい.'},
        {errorCode: 3, errorMessage: '店状態の取得に失敗しました.'}
      ]
      const result = await axios.get(
          '/api/v1/status'
        ).catch(
          (error) => {
            axiosErrorMessage = error.message
            console.log(error)
          }
        )
      if (result !== undefined) {
        this.$store.dispatch('commitUpdateShopStatus', {shopStatusID: result.data.status_id})
      } else {
        this.$store.dispatch('commitUpdateShopStatus', {shopStatusID: 0})
      }
      return this.apiErrorCode(axiosErrorMessage, errorMessages)
    },
    async callAPIUpdateWaitingNoState() {
      let axiosErrorMessage
      let errorMessages = [
        {errorCode: 1, errorMessage: '順番待ち番号の更新に失敗しました. ネットワーク接続を確認して下さい.'},
        {errorCode: 2, errorMessage: '順番待ち番号の更新に失敗しました. 再ログインして下さい.'},
        {errorCode: 3, errorMessage: '順番待ち番号の更新に失敗しました.'}
      ]
      const result = await axios.patch(
        '/api/v1/waiting', {
          waiting_numbers: this.updateWaitingNoState
        }, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('AccessToken')}`
          },
          params: {
            'key': this.masterKey
          }
        }).catch(
          (error) => {
            axiosErrorMessage = error.message
            console.log(error)
          }
        )
      if (result !== undefined) console.log('info:Update waitingNo successed.')
      return this.apiErrorCode(axiosErrorMessage, errorMessages)
    },
    async callAPIIssueWaitNumber() {
      let axiosErrorMessage
      let errorMessages = [
        {errorCode: 1, errorMessage: '順番待ち番号の発行に失敗しました. ネットワーク接続を確認してください.'},
        {errorCode: 2, errorMessage: '順番待ち番号の発行に失敗しました. 再ログインして下さい.'},
        {errorCode: 3, errorMessage: '順番待ち番号の発行に失敗しました.'}
      ]
      let result = await axios.post(
          '/api/v1/waiting', {}, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('AccessToken')}`
          },
          params: {
            'key': this.masterKey
          }
        }).catch(
          (error) => {
            axiosErrorMessage = error.message
            console.log(error)
          }
        )
      if (result !== undefined) {
        this.$store.dispatch('commitUpdateAPIWaitingNoState', {updateObject: result.data.wait_number})
      }
      return this.apiErrorCode(axiosErrorMessage, errorMessages)
    },
    async callAPIWaitNumberReset() {
      let axiosErrorMessage
      let errorMessages = [
        {errorCode: 1, errorMessage: '順番待ち番号のリセットに失敗しました. ネットワーク接続を確認してください.'},
        {errorCode: 2, errorMessage: '順番待ち番号のリセットに失敗しました. 再ログインして下さい.'},
        {errorCode: 3, errorMessage: '順番待ち番号のリセットに失敗しました.'}
      ]
      let result = await axios.delete(
        '/api/v1/waiting', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('AccessToken')}`
          }
        }).catch(
          (error) => {
            axiosErrorMessage = error.message
            console.log(error)
          }
        )
      if (result !== undefined) {
        if (result.data.success) {
          await this.callApiGetWaitNumber()
        }
      }
      return this.apiErrorCode(axiosErrorMessage, errorMessages)
    },
    async callApiGetWaitNumber() {
      let axiosErrorMessage
      let errorMessages = [
        {errorCode: 1, errorMessage: '順番待ち番号の取得に失敗しました. ネットワーク接続を確認してください.'},
        {errorCode: 2, errorMessage: '順番待ち番号の取得に失敗しました. 再ログインして下さい.'},
        {errorCode: 3, errorMessage: '順番待ち番号の取得に失敗しました.'}
      ]
      let result = await axios.get(
          '/api/v1/waiting'
        ).catch(
          (error) => {
            axiosErrorMessage = error.message
            console.log(error)
          }
        )
      if (result !== undefined) {
        this.$store.dispatch('commitUpdateAPIWaitingNoState', {updateObject: result.data.wait_number})
      }
      return this.apiErrorCode(axiosErrorMessage, errorMessages)
    },
    async callAPILogout() {
      let axiosErrorMessage
      let errorMessages = [
        {errorCode: 1, errorMessage: 'ログアウト処理に失敗しました. ネットワーク接続を確認してください.'},
        {errorCode: 2, errorMessage: '既にログアウトされています. '},
        {errorCode: 3, errorMessage: 'ログアウト処理に失敗しました.'}
      ]
      let result = await axios.delete(
        '/api/v1/auth/logout', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('AccessToken')}`,
          }
      }).catch(
        (error) => {
          axiosErrorMessage = error.message
        }
      )
      if (result !== undefined) {
        if (result.data.success) {
          let credential = {
            isLogin: false,
            accessToken: ''
          }
          this.$store.dispatch('commitUpdateLoginCredential', {credential})
        }
      }
      return this.apiErrorCode(axiosErrorMessage, errorMessages)
    }
  },
  setup() {
    
  },
}
</script>

<style scoped>
  .container {
    display: flex;
    flex-direction: column;
    justify-content: center;
  }
  .controll-container{
    width: 570px;
    padding: 20px;
  }
  .button-list {
    margin-bottom: 20px;
  }
  .cut-status {
    height: 50px;
    margin-bottom: 20px;
    font-size: 1.5em;
  }
  .cut-number {
    height: 80px;
    margin-bottom: 20px;
    font-size: 3.0em;
  }
  .internal-cut-number:hover {
    cursor: pointer;
  }
  #outer-number-list {
    height: 65px;
  }
  .number-list-box {
    position: relative;
    width: 28%;
  }
  .number-list-label {
    position: absolute;
    top: 0;
    width: 90%;
    font-size: 1.5em;
    padding: 10px 0;
    background: #CCC;
    z-index: 5;
  }
  .number-list-group {
    position: absolute;
    width: 100%;
    top: calc(10px + (1.5em / 2));
    padding-top: calc(5px + (1.5em / 2));
  }
  .number-list {
    width: 100%;
    padding: 10px 0;
  }
  .list-object {
    width: 80%;
    padding: 10px 0;
    font-size: 1.2em;
  }
  .list-object:hover {
    cursor: pointer;
  }
  .list-object-margin:not(:first-of-type) {
    margin-top: 5px;
  }
  .page-bottom-margin {
    margin-bottom: 20px;
  }
  .space-between {
    display: flex;
    justify-content: space-between;
  }
  button:hover {
    cursor: pointer;
  }
  .wide-button {
    width: 100%;
    font-size: 1.2em;
    padding: 10px 0;
    margin-bottom: 20px;
  }
  .button {
    padding: 10px 20px;
    font-size: 1.2em;
  }
  .bg-white {
    background: white;
  }
  .center {
    display: flex;
    justify-content: center;
    align-items: center;
  }
  .center-column {
    display: flex;
    flex-direction: column;
    align-items: center;
  }
  .space-around {
    display: flex;
    flex-direction: row;
    justify-content: space-around;
  }
  .border-radius {
    border: 1px solid black;
    border-radius: 10px;
  }
  .border-solid {
    border: 1px solid black;
  }
</style>
