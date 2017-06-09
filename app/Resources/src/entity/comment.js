import BaseEntity from './baseEntity'

export default class Comment extends BaseEntity {
  _token: string
  _createdAt: Object
  _content: Object
  _user: Object
  _children: [Comment]

  constructor (data) {
    super()
    this._token = this.get('token', data, '')
    this._createdAt = this.get('created_at', data, {})
    this._content = this.get('content', data, '')
    this._user = this.get('user', data, {})
    this._children = []
    if (data.children) {
      for (let i = 0; i < data.children.length; i++) {
        this._children.push(new Comment(data.children[i]))
      }
    }
  }

  getChildren () : [Comment] {
    return this._children
  }

  getUser () : Object {
    return this._user
  }

  getConent () : string {
    return this._content
  }

  getCreatedAt () : Object {
    return this._createdAt
  }

  getToken () : string {
    return this._token
  }

}
