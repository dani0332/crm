(self.webpackChunk = self.webpackChunk || []).push([
  [48],
  {
    2163: (e, t, l) => {
      'use strict';
      var n = l(9963),
        o = l(6252),
        a = l(9285),
        r = l(9145),
        i = l(3577),
        u = l(2610),
        s = { class: 'flex w-full min-h-screen overflow-x-clip' },
        d = {
          class:
            'border-b h-[4rem] shrink-0 flex items-center justify-center relative',
        },
        c = { class: 'flex items-center justify-center px-2 w-full lg:px-4' },
        m = [
          (0, o._)(
            'svg',
            {
              class: 'h-6 w-6',
              width: '24',
              height: '24',
              viewBox: '0 0 24 24',
              fill: 'none',
              xmlns: 'http://www.w3.org/2000/svg',
            },
            [
              (0, o._)('path', {
                d: 'M20.25 7.5L16 12L20.25 16.5M3.75 12H12M3.75 17.25H16M3.75 6.75H16',
                stroke: 'currentColor',
                'stroke-width': '2',
                'stroke-linecap': 'round',
                'stroke-linejoin': 'round',
              }),
            ],
            -1,
          ),
        ],
        p = (0, o._)(
          'a',
          { href: '/', class: 'block w-full' },
          [
            (0, o._)('img', {
              src: '/images/logo.png',
              alt: 'IMCRM',
              class: 'w-full',
            }),
          ],
          -1,
        ),
        f = {
          class:
            'flex-1 text-[0.8rem] pb-6 font-medium overflow-x-hidden overflow-y-auto flex flex-col bg-gradient-to-b from-primary-500 to-primary-700 text-white',
        },
        _ = { class: 'pl-3 py-2.5 hover:bg-black/10' },
        v = ['href'],
        g = { class: 'pt-1' },
        w = ['href'],
        h = {
          class:
            'flex-col gap-y-6 w-screen flex-1 h-full transition-all lg:pl-[var(--sidebar-width)]',
        },
        b = {
          class:
            'sticky top-0 z-20 flex h-16 w-full shrink-0 items-center border-b bg-white',
        },
        y = {
          class:
            'flex items-center justify-between w-full px-2 sm:px-4 md:px-6 lg:px-8',
        },
        x = [
          (0, o._)(
            'svg',
            {
              class: 'w-6 h-6',
              xmlns: 'http://www.w3.org/2000/svg',
              fill: 'none',
              viewBox: '0 0 24 24',
              'stroke-width': '2',
              stroke: 'currentColor',
            },
            [
              (0, o._)('path', {
                'stroke-linecap': 'round',
                'stroke-linejoin': 'round',
                d: 'M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5',
              }),
            ],
            -1,
          ),
        ],
        U = (0, o._)(
          'svg',
          {
            xmlns: 'http://www.w3.org/2000/svg',
            fill: 'none',
            viewBox: '0 0 24 24',
            'stroke-width': '2',
            stroke: 'currentColor',
            class: 'w-6 h-6 text-red-600',
          },
          [
            (0, o._)('path', {
              'stroke-linecap': 'round',
              'stroke-linejoin': 'round',
              d: 'M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75',
            }),
          ],
          -1,
        ),
        S = (0, o._)('span', { class: 'text-sm font-semibold' }, 'Logout', -1),
        V = { class: 'flex-1 w-full p-4 mx-auto md:px-6 lg:px-8 max-w-full' };
      const k = {
          __name: 'MainLayout',
          setup: function (e) {
            var t = (0, a.qt)(),
              l = (0, o.Fl)(function () {
                return t.props.auth.user;
              }),
              r = (0, o.Fl)(function () {
                return t.props.sidebar;
              }),
              k = (0, u.iH)(!1);
            return (
              a.Nd.on('navigate', function () {
                k.value = !1;
              }),
              function (e, t) {
                var q = (0, o.up)('x-icon'),
                  C = (0, o.up)('x-collapse'),
                  W = (0, o.up)('x-button'),
                  z = (0, o.up)('x-popover-container'),
                  E = (0, o.up)('x-popover'),
                  D = (0, o.up)('XNotifications');
                return (
                  (0, o.wg)(),
                  (0, o.iD)('main', s, [
                    (0, o._)(
                      'aside',
                      {
                        class: (0, i.C_)([
                          k.value
                            ? 'translate-x-0'
                            : '-translate-x-full lg:translate-x-0',
                          'fixed inset-y-0 left-0 z-30 flex flex-col h-screen overflow-hidden shadow-2xl transition-all bg-white lg:border-r lg:z-0 max-w-[17em] lg:max-w-[var(--sidebar-width)]',
                        ]),
                      },
                      [
                        (0, o._)('header', d, [
                          (0, o._)('div', c, [
                            (0, o._)(
                              'button',
                              {
                                type: 'button',
                                class:
                                  'shrink-0 lg:hidden flex items-center justify-center w-10 h-10 text-primary-500 rounded-full hover:bg-gray-500/5 focus:bg-primary-500/10 focus:outline-none',
                                'aria-label': 'Collapse sidebar',
                                onClick:
                                  t[0] ||
                                  (t[0] = (0, n.iM)(
                                    function (e) {
                                      return (k.value = !k.value);
                                    },
                                    ['prevent'],
                                  )),
                              },
                              m,
                            ),
                            p,
                          ]),
                        ]),
                        (0, o._)('nav', f, [
                          ((0, o.wg)(!0),
                          (0, o.iD)(
                            o.HY,
                            null,
                            (0, o.Ko)((0, u.SU)(r), function (t) {
                              return (
                                (0, o.wg)(),
                                (0, o.iD)(
                                  o.HY,
                                  null,
                                  [
                                    t.children.length > 0
                                      ? ((0, o.wg)(),
                                        (0, o.j4)(
                                          C,
                                          {
                                            key: 0,
                                            'show-icon': '',
                                            expanded: t.children.some(function (
                                              t,
                                            ) {
                                              return e.$page.url.startsWith(
                                                t.url,
                                              );
                                            }),
                                          },
                                          {
                                            default: (0, o.w5)(function () {
                                              return [
                                                (0, o._)(
                                                  'div',
                                                  _,
                                                  (0, i.zw)(t.title),
                                                  1,
                                                ),
                                              ];
                                            }),
                                            content: (0, o.w5)(function () {
                                              return [
                                                ((0, o.wg)(!0),
                                                (0, o.iD)(
                                                  o.HY,
                                                  null,
                                                  (0, o.Ko)(
                                                    t.children,
                                                    function (t) {
                                                      return (
                                                        (0, o.wg)(),
                                                        (0, o.iD)(
                                                          'a',
                                                          {
                                                            href: t.url,
                                                            class: (0, i.C_)([
                                                              'pl-3 py-2 flex gap-2 items-center hover:bg-black/10',
                                                              {
                                                                '!bg-primary-800':
                                                                  e.$page.url.startsWith(
                                                                    t.url,
                                                                  ),
                                                              },
                                                            ]),
                                                          },
                                                          [
                                                            (0, o.Wm)(
                                                              q,
                                                              {
                                                                icon: t
                                                                  .attributes
                                                                  .icon
                                                                  ? t.attributes
                                                                      .icon
                                                                  : 'box',
                                                              },
                                                              null,
                                                              8,
                                                              ['icon'],
                                                            ),
                                                            (0, o._)(
                                                              'span',
                                                              g,
                                                              (0, i.zw)(
                                                                t.title,
                                                              ),
                                                              1,
                                                            ),
                                                          ],
                                                          10,
                                                          v,
                                                        )
                                                      );
                                                    },
                                                  ),
                                                  256,
                                                )),
                                              ];
                                            }),
                                            _: 2,
                                          },
                                          1032,
                                          ['expanded'],
                                        ))
                                      : ((0, o.wg)(),
                                        (0, o.iD)(
                                          'a',
                                          {
                                            key: 1,
                                            href: t.url,
                                            class: (0, i.C_)([
                                              'pl-3 py-2.5 flex gap-2 items-center hover:bg-black/10',
                                              {
                                                '!bg-primary-800':
                                                  e.$page.url.startsWith(t.url),
                                              },
                                            ]),
                                          },
                                          [
                                            t.attributes.icon
                                              ? ((0, o.wg)(),
                                                (0, o.j4)(
                                                  q,
                                                  {
                                                    key: 0,
                                                    icon: t.attributes.icon,
                                                  },
                                                  null,
                                                  8,
                                                  ['icon'],
                                                ))
                                              : (0, o.kq)('', !0),
                                            (0, o.Uk)(
                                              ' ' + (0, i.zw)(t.title),
                                              1,
                                            ),
                                          ],
                                          10,
                                          w,
                                        )),
                                  ],
                                  64,
                                )
                              );
                            }),
                            256,
                          )),
                        ]),
                      ],
                      2,
                    ),
                    k.value
                      ? ((0, o.wg)(),
                        (0, o.iD)('div', {
                          key: 0,
                          class:
                            'bg-black/75 backdrop-blur-sm w-full h-full fixed inset-0 z-20 lg:hidden',
                          onClick:
                            t[1] ||
                            (t[1] = (0, n.iM)(
                              function (e) {
                                return (k.value = !1);
                              },
                              ['prevent'],
                            )),
                        }))
                      : (0, o.kq)('', !0),
                    (0, o._)('article', h, [
                      (0, o._)('header', b, [
                        (0, o._)('div', y, [
                          (0, o._)('div', null, [
                            (0, o._)(
                              'button',
                              {
                                type: 'button',
                                class:
                                  'shrink-0 flex lg:hidden items-center justify-center w-10 h-10 text-primary-500 rounded-full hover:bg-gray-500/5 focus:bg-primary-500/10 focus:outline-none',
                                'aria-label': 'Open sidebar',
                                onClick:
                                  t[2] ||
                                  (t[2] = (0, n.iM)(
                                    function (e) {
                                      return (k.value = !k.value);
                                    },
                                    ['prevent'],
                                  )),
                              },
                              x,
                            ),
                          ]),
                          (0, o._)('div', null, [
                            (0, o.Wm)(
                              E,
                              { align: 'right', block: '' },
                              {
                                content: (0, o.w5)(function () {
                                  return [
                                    (0, o.Wm)(
                                      z,
                                      { class: 'p-2' },
                                      {
                                        default: (0, o.w5)(function () {
                                          return [
                                            (0, o.Wm)(
                                              (0, u.SU)(a.rU),
                                              {
                                                href: '/logout',
                                                method: 'post',
                                                as: 'button',
                                                type: 'submit',
                                                class:
                                                  'flex gap-2 items-center',
                                              },
                                              {
                                                default: (0, o.w5)(function () {
                                                  return [U, S];
                                                }),
                                                _: 1,
                                              },
                                            ),
                                          ];
                                        }),
                                        _: 1,
                                      },
                                    ),
                                  ];
                                }),
                                default: (0, o.w5)(function () {
                                  return [
                                    (0, o.Wm)(W, null, {
                                      default: (0, o.w5)(function () {
                                        return [
                                          (0, o.Uk)(
                                            (0, i.zw)((0, u.SU)(l).name),
                                            1,
                                          ),
                                        ];
                                      }),
                                      _: 1,
                                    }),
                                  ];
                                }),
                                _: 1,
                              },
                            ),
                          ]),
                        ]),
                      ]),
                      (0, o._)('div', V, [
                        (0, o.Wm)(
                          D,
                          { 'inject-key': 'toast' },
                          {
                            default: (0, o.w5)(function () {
                              return [(0, o.WI)(e.$slots, 'default')];
                            }),
                            _: 3,
                          },
                        ),
                      ]),
                    ]),
                  ])
                );
              }
            );
          },
        },
        q = {
          next: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"> <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 4.5l7.5 7.5-7.5 7.5m-6-15l7.5 7.5-7.5 7.5" /> </svg>',
          prev: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"> <path stroke-linecap="round" stroke-linejoin="round" d="M18.75 19.5l-7.5-7.5 7.5-7.5m-6 15L5.25 12l7.5-7.5" /> </svg>',
          health:
            '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="currentColor" d="M10.5 13H8v-3h2.5V7.5h3V10H16v3h-2.5v2.5h-3V13zM12 2L4 5v6.09c0 5.05 3.41 9.76 8 10.91c4.59-1.15 8-5.86 8-10.91V5l-8-3zm6 9.09c0 4-2.55 7.7-6 8.83c-3.45-1.13-6-4.82-6-8.83v-4.7l6-2.25l6 2.25v4.7z"/></svg>',
          car: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="currentColor" d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5s1.5.67 1.5 1.5s-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/></svg>',
          travel:
            '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="currentColor" d="M22 16v-2l-8.5-5V3.5c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5V9L2 14v2l8.5-2.5V19L8 20.5V22l4-1l4 1v-1.5L13.5 19v-5.5L22 16z"/></svg>',
          life: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="currentColor" d="M16 4c0-1.11.89-2 2-2s2 .89 2 2s-.89 2-2 2s-2-.89-2-2zm4 18v-6h2.5l-2.54-7.63A2.01 2.01 0 0 0 18.06 7h-.12a2 2 0 0 0-1.9 1.37l-.86 2.58c1.08.6 1.82 1.73 1.82 3.05v8h3zm-7.5-10.5c.83 0 1.5-.67 1.5-1.5s-.67-1.5-1.5-1.5S11 9.17 11 10s.67 1.5 1.5 1.5zM5.5 6c1.11 0 2-.89 2-2s-.89-2-2-2s-2 .89-2 2s.89 2 2 2zm2 16v-7H9V9c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v6h1.5v7h4zm6.5 0v-4h1v-4c0-.82-.68-1.5-1.5-1.5h-2c-.82 0-1.5.68-1.5 1.5v4h1v4h3z"/></svg>',
          home: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="currentColor" d="m14.16 10.4l-5-3.57c-.7-.5-1.63-.5-2.32 0l-5 3.57c-.53.38-.84.98-.84 1.63V20c0 .55.45 1 1 1h4v-6h4v6h4c.55 0 1-.45 1-1v-7.97c0-.65-.31-1.25-.84-1.63z"/><path fill="currentColor" d="M21.03 3h-9.06C10.88 3 10 3.88 10 4.97l.09.09c.08.05.16.09.24.14l5 3.57c.76.54 1.3 1.34 1.54 2.23H19v2h-2v2h2v2h-2v4h4.03c1.09 0 1.97-.88 1.97-1.97V4.97C23 3.88 22.12 3 21.03 3zM19 9h-2V7h2v2z"/></svg>',
          pet: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><circle cx="4.5" cy="9.5" r="2.5" fill="currentColor"/><circle cx="9" cy="5.5" r="2.5" fill="currentColor"/><circle cx="15" cy="5.5" r="2.5" fill="currentColor"/><circle cx="19.5" cy="9.5" r="2.5" fill="currentColor"/><path fill="currentColor" d="M17.34 14.86c-.87-1.02-1.6-1.89-2.48-2.91c-.46-.54-1.05-1.08-1.75-1.32c-.11-.04-.22-.07-.33-.09c-.25-.04-.52-.04-.78-.04s-.53 0-.79.05c-.11.02-.22.05-.33.09c-.7.24-1.28.78-1.75 1.32c-.87 1.02-1.6 1.89-2.48 2.91c-1.31 1.31-2.92 2.76-2.62 4.79c.29 1.02 1.02 2.03 2.33 2.32c.73.15 3.06-.44 5.54-.44h.18c2.48 0 4.81.58 5.54.44c1.31-.29 2.04-1.31 2.33-2.32c.31-2.04-1.3-3.49-2.61-4.8z"/></svg>',
          empty:
            '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="currentColor" d="M14.71 10.79a1 1 0 0 0-1.42 0L12 12.09l-1.29-1.3a1 1 0 0 0-1.42 1.42l1.3 1.29l-1.3 1.29a1 1 0 0 0 0 1.42a1 1 0 0 0 1.42 0l1.29-1.3l1.29 1.3a1 1 0 0 0 1.42 0a1 1 0 0 0 0-1.42l-1.3-1.29l1.3-1.29a1 1 0 0 0 0-1.42ZM19 5.5h-6.28l-.32-1a3 3 0 0 0-2.84-2H5a3 3 0 0 0-3 3v13a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3v-10a3 3 0 0 0-3-3Zm1 13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-13a1 1 0 0 1 1-1h4.56a1 1 0 0 1 .95.68l.54 1.64a1 1 0 0 0 .95.68h7a1 1 0 0 1 1 1Z"/></svg>',
          box: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"><path d="M21 7.353v9.294a.6.6 0 0 1-.309.525l-8.4 4.666a.6.6 0 0 1-.582 0l-8.4-4.666A.6.6 0 0 1 3 16.647V7.353a.6.6 0 0 1 .309-.524l8.4-4.667a.6.6 0 0 1 .582 0l8.4 4.667a.6.6 0 0 1 .309.524Z"/><path d="m3.528 7.294l8.18 4.544a.6.6 0 0 0 .583 0l8.209-4.56M12 21v-9"/></g></svg>',
          graph:
            '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="currentColor" d="M14.06 9.94L12 9l2.06-.94L15 6l.94 2.06L18 9l-2.06.94L15 12l-.94-2.06zM4 14l.94-2.06L7 11l-2.06-.94L4 8l-.94 2.06L1 11l2.06.94L4 14zm4.5-5l1.09-2.41L12 5.5L9.59 4.41L8.5 2L7.41 4.41L5 5.5l2.41 1.09L8.5 9zm-4 11.5l6-6.01l4 4L23 8.93l-1.41-1.41l-7.09 7.97l-4-4L3 19l1.5 1.5z"/></svg>',
          bar: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="currentColor" d="M4 9h4v11H4zm0-5h4v4H4zm6 3h4v4h-4zm6 3h4v4h-4zm0 5h4v5h-4zm-6-3h4v8h-4z"/></svg>',
          person:
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"> <path d="M10 8a3 3 0 100-6 3 3 0 000 6zM3.465 14.493a1.23 1.23 0 00.41 1.412A9.957 9.957 0 0010 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 00-13.074.003z" /> </svg>',
          money:
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"> <path fill-rule="evenodd" d="M1 4a1 1 0 011-1h16a1 1 0 011 1v8a1 1 0 01-1 1H2a1 1 0 01-1-1V4zm12 4a3 3 0 11-6 0 3 3 0 016 0zM4 9a1 1 0 100-2 1 1 0 000 2zm13-1a1 1 0 11-2 0 1 1 0 012 0zM1.75 14.5a.75.75 0 000 1.5c4.417 0 8.693.603 12.749 1.73 1.111.309 2.251-.512 2.251-1.696v-.784a.75.75 0 00-1.5 0v.784a.272.272 0 01-.35.25A49.043 49.043 0 001.75 14.5z" clip-rule="evenodd" /> </svg>',
          calendar:
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"> <path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" /> </svg>',
          company:
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"> <path fill-rule="evenodd" d="M6 3.75A2.75 2.75 0 018.75 1h2.5A2.75 2.75 0 0114 3.75v.443c.572.055 1.14.122 1.706.2C17.053 4.582 18 5.75 18 7.07v3.469c0 1.126-.694 2.191-1.83 2.54-1.952.599-4.024.921-6.17.921s-4.219-.322-6.17-.921C2.694 12.73 2 11.665 2 10.539V7.07c0-1.321.947-2.489 2.294-2.676A41.047 41.047 0 016 4.193V3.75zm6.5 0v.325a41.622 41.622 0 00-5 0V3.75c0-.69.56-1.25 1.25-1.25h2.5c.69 0 1.25.56 1.25 1.25zM10 10a1 1 0 00-1 1v.01a1 1 0 001 1h.01a1 1 0 001-1V11a1 1 0 00-1-1H10z" clip-rule="evenodd" /> <path d="M3 15.055v-.684c.126.053.255.1.39.142 2.092.642 4.313.987 6.61.987 2.297 0 4.518-.345 6.61-.987.135-.041.264-.089.39-.142v.684c0 1.347-.985 2.53-2.363 2.686a41.454 41.454 0 01-9.274 0C3.985 17.585 3 16.402 3 15.055z" /> </svg>',
        };
      var C,
        W = l(8010),
        z =
          (null === (C = window.document.getElementsByTagName('title')[0]) ||
          void 0 === C
            ? void 0
            : C.innerText) || 'IMCRM';
      (0, a.yP)({
        title: function (e) {
          return ''.concat(e, ' - ').concat(z);
        },
        resolve: function (e) {
          var t = l(4053)('./'.concat(e));
          return (t.default.layout = t.default.layout || k), t;
        },
        setup: function (e) {
          var t = e.el,
            l = e.App,
            a = e.props,
            i = e.plugin;
          (0, n.ri)({
            name: 'IMCRM',
            mounted: function () {
              var e;
              null === (e = document.querySelector('[data-page]')) ||
                void 0 === e ||
                e.removeAttribute('data-page');
            },
            render: function () {
              return (0, o.h)(l, a);
            },
          })
            .component('DataTable', W.Z)
            .use(i)
            .use(r.ZP, { icons: q })
            .mount(t);
        },
      });
    },
    2584: () => {},
    2688: () => {},
    2726: (e, t, l) => {
      'use strict';
      l.d(t, { Z: () => w });
      var n = l(6252),
        o = l(3577),
        a = l(2610),
        r = l(99),
        i = l(2600),
        u = {
          class:
            'group relative x-select inline-block align-bottom text-left focus:outline-none mb-3 w-full',
        },
        s = { class: 'font-medium text-gray-800 mb-1' },
        d = { class: 'pt-1 px-2 -mb-2' },
        c = {
          key: 0,
          class:
            'relative cursor-default select-none py-2 px-4 text-gray-600 text-xs',
        },
        m = { class: 'flex-1 truncate py-px' },
        p = { class: 'ml-1 shrink-0' },
        f = {
          key: 0,
          xmlns: 'http://www.w3.org/2000/svg',
          class: 'shrink-0 inline h-5 w-5 stroke-2',
          'stroke-linejoin': 'round',
          'stroke-linecap': 'round',
          stroke: 'currentColor',
          fill: 'none',
          viewBox: '0 0 24 24',
          'data-v-27199701': '',
        },
        _ = [(0, n._)('path', { d: 'M5 13l4 4L19 7' }, null, -1)],
        v = (0, n._)(
          'div',
          {
            class:
              'pointer-events-none absolute inset-y-0 right-0 flex items-center px-2',
          },
          [
            (0, n._)(
              'svg',
              {
                xmlns: 'http://www.w3.org/2000/svg',
                class: 'shrink-0 x-icon inline h-5 w-5 stroke-2 text-gray-500',
                'stroke-linejoin': 'round',
                'stroke-linecap': 'round',
                stroke: 'currentColor',
                fill: 'none',
                viewBox: '0 0 24 24',
              },
              [(0, n._)('path', { d: 'M8 9l4-4 4 4m0 6l-4 4-4-4' })],
            ),
          ],
          -1,
        ),
        g = { key: 0, class: 'text-sm text-red-500 mt-1' };
      const w = {
        __name: 'ComboBox',
        props: {
          label: { required: !0, type: String },
          options: { type: Array, default: [] },
          modelValue: { type: [String, Array, Number], default: [] },
          single: { type: Boolean, default: !1 },
          placeholder: { type: String, default: 'Select an option' },
          searchPlaceholder: { type: String, default: 'Search options' },
          hasError: { type: Boolean, default: !1 },
        },
        emits: ['update:modelValue'],
        setup: function (e, t) {
          var l = t.emit,
            w = e,
            h = (0, n.Fl)({
              get: function () {
                return w.single
                  ? []
                  : w.options.filter(function (e) {
                      return w.modelValue.includes(e.value);
                    });
              },
              set: function (e) {
                if (w.single) l('update:modelValue', e.value);
                else {
                  var t = e.map(function (e) {
                    return e.value;
                  });
                  l('update:modelValue', t);
                }
              },
            }),
            b = (0, a.iH)(''),
            y = (0, n.Fl)(function () {
              return '' === b.value
                ? w.options
                : w.options.filter(function (e) {
                    return e.label
                      .toLowerCase()
                      .includes(b.value.toLowerCase());
                  });
            });
          return function (e, t) {
            var l = (0, n.up)('x-input');
            return (
              (0, n.wg)(),
              (0, n.iD)('label', u, [
                (0, n._)('p', s, (0, o.zw)(w.label), 1),
                (0, n.Wm)(
                  (0, a.SU)(r.hQ),
                  {
                    modelValue: (0, a.SU)(h),
                    'onUpdate:modelValue':
                      t[2] ||
                      (t[2] = function (e) {
                        return (0, a.dq)(h) ? (h.value = e) : null;
                      }),
                    multiple: !w.single,
                    as: 'div',
                    class: 'relative',
                  },
                  {
                    default: (0, n.w5)(function () {
                      var e;
                      return [
                        (0, n.Wm)(
                          (0, a.SU)(r.gA),
                          {
                            displayValue: function (e) {
                              return null == e ? void 0 : e.label;
                            },
                            class: (0, o.C_)([
                              { 'border-red-500': w.hasError },
                              'appearance-none block placeholder-gray-400 outline-transparent outline outline-2 outline-offset-[-1px] transition-all duration-150 ease-in-out border-gray-300 border shadow-sm rounded-md hover:border-gray-400 px-3 py-2 bg-white text-gray-700 focus:outline-sky-500 w-full',
                            ]),
                            placeholder: w.placeholder,
                            value: w.single
                              ? null ===
                                  (e = w.options.find(function (e) {
                                    return e.value === w.modelValue;
                                  })) || void 0 === e
                                ? void 0
                                : e.label
                              : ''.concat((0, a.SU)(h).length, ' Selected'),
                            readonly: '',
                          },
                          null,
                          8,
                          ['displayValue', 'class', 'placeholder', 'value'],
                        ),
                        (0, n.Wm)((0, a.SU)(r.Q$), {
                          class: 'absolute bottom-0 right-0 w-full h-full',
                        }),
                        (0, n.Wm)(
                          (0, a.SU)(i.Q),
                          {
                            leave: 'transition ease-in duration-100',
                            leaveFrom: 'opacity-100',
                            leaveTo: 'opacity-0',
                            onAfterLeave:
                              t[1] ||
                              (t[1] = function (e) {
                                return (b.value = '');
                              }),
                          },
                          {
                            default: (0, n.w5)(function () {
                              return [
                                (0, n.Wm)(
                                  (0, a.SU)(r.L5),
                                  {
                                    class:
                                      'absolute z-10 mt-1 max-h-48 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm',
                                  },
                                  {
                                    default: (0, n.w5)(function () {
                                      return [
                                        (0, n._)('li', d, [
                                          (0, n.Wm)(
                                            l,
                                            {
                                              size: 'xs',
                                              modelValue: b.value,
                                              'onUpdate:modelValue':
                                                t[0] ||
                                                (t[0] = function (e) {
                                                  return (b.value = e);
                                                }),
                                              placeholder: w.searchPlaceholder,
                                              class: 'w-full',
                                            },
                                            null,
                                            8,
                                            ['modelValue', 'placeholder'],
                                          ),
                                        ]),
                                        0 === (0, a.SU)(y).length &&
                                        '' !== b.value
                                          ? ((0, n.wg)(),
                                            (0, n.iD)(
                                              'li',
                                              c,
                                              ' No results found ',
                                            ))
                                          : (0, n.kq)('', !0),
                                        ((0, n.wg)(!0),
                                        (0, n.iD)(
                                          n.HY,
                                          null,
                                          (0, n.Ko)((0, a.SU)(y), function (e) {
                                            return (
                                              (0, n.wg)(),
                                              (0, n.j4)(
                                                (0, a.SU)(r.O2),
                                                {
                                                  as: 'template',
                                                  key: e.label,
                                                  value: e,
                                                },
                                                {
                                                  default: (0, n.w5)(function (
                                                    t,
                                                  ) {
                                                    var l = t.selected;
                                                    return [
                                                      (0, n._)(
                                                        'li',
                                                        {
                                                          class: (0, o.C_)([
                                                            {
                                                              'text-primary': l,
                                                            },
                                                            'relative flex items-center whitespace-nowrap px-3 text-sm cursor-pointer py-1.5 hover:bg-primary-50',
                                                          ]),
                                                        },
                                                        [
                                                          (0, n._)(
                                                            'span',
                                                            m,
                                                            (0, o.zw)(e.label),
                                                            1,
                                                          ),
                                                          (0, n._)('span', p, [
                                                            l
                                                              ? ((0, n.wg)(),
                                                                (0, n.iD)(
                                                                  'svg',
                                                                  f,
                                                                  _,
                                                                ))
                                                              : (0, n.kq)(
                                                                  '',
                                                                  !0,
                                                                ),
                                                          ]),
                                                        ],
                                                        2,
                                                      ),
                                                    ];
                                                  }),
                                                  _: 2,
                                                },
                                                1032,
                                                ['value'],
                                              )
                                            );
                                          }),
                                          128,
                                        )),
                                      ];
                                    }),
                                    _: 1,
                                  },
                                ),
                              ];
                            }),
                            _: 1,
                          },
                        ),
                        v,
                      ];
                    }),
                    _: 1,
                  },
                  8,
                  ['modelValue', 'multiple'],
                ),
                w.hasError
                  ? ((0, n.wg)(), (0, n.iD)('p', g, ' This field is required '))
                  : (0, n.kq)('', !0),
              ])
            );
          };
        },
      };
    },
    3356: (e, t, l) => {
      'use strict';
      l.r(t), l.d(t, { default: () => T });
      var n = l(6252),
        o = l(2610),
        a = l(3577),
        r = l(9963),
        i = l(9285),
        u = l(3299),
        s = l(8433);
      function d(e, t) {
        var l = Object.keys(e);
        if (Object.getOwnPropertySymbols) {
          var n = Object.getOwnPropertySymbols(e);
          t &&
            (n = n.filter(function (t) {
              return Object.getOwnPropertyDescriptor(e, t).enumerable;
            })),
            l.push.apply(l, n);
        }
        return l;
      }
      function c(e) {
        for (var t = 1; t < arguments.length; t++) {
          var l = null != arguments[t] ? arguments[t] : {};
          t % 2
            ? d(Object(l), !0).forEach(function (t) {
                m(e, t, l[t]);
              })
            : Object.getOwnPropertyDescriptors
            ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(l))
            : d(Object(l)).forEach(function (t) {
                Object.defineProperty(
                  e,
                  t,
                  Object.getOwnPropertyDescriptor(l, t),
                );
              });
        }
        return e;
      }
      function m(e, t, l) {
        return (
          t in e
            ? Object.defineProperty(e, t, {
                value: l,
                enumerable: !0,
                configurable: !0,
                writable: !0,
              })
            : (e[t] = l),
          e
        );
      }
      var p = { class: 'flex justify-between items-center' },
        f = (0, n._)(
          'h2',
          { class: 'text-xl font-semibold' },
          'Health List',
          -1,
        ),
        _ = { class: 'space-x-3' },
        v = { key: 0, class: 'flex w-full h-[85vh] space-x-4 overflow-auto' },
        g = {
          class:
            'flex flex-col flex-shrink-0 gap-1.5 p-3 border-b border-gray-300 bg-white text-xs',
        },
        w = { class: 'font-semibold text-sm' },
        h = { class: 'flex justify-between gap-1' },
        b = (0, n._)('span', null, 'Total Leads', -1),
        y = { class: 'flex justify-between gap-1' },
        x = (0, n._)('span', null, 'Total Premium', -1),
        U = { class: 'flex flex-col px-2 pb-2 overflow-auto' },
        S = { key: 0, class: 'text-center p-4' },
        V = { key: 1, class: 'text-center text-xs text-gray-800 p-4' },
        k = (0, n._)('p', null, 'No Leads Found', -1),
        q = ['href'],
        C = { class: 'font-semibold text-sm' },
        W = { class: 'flex items-center gap-2' },
        z = { class: 'text-xs' },
        E = { key: 0, class: 'flex items-center gap-2' },
        D = { class: 'text-xs' },
        A = { class: 'flex items-center gap-2' },
        M = { class: 'text-xs' },
        O = { class: 'flex items-center gap-2' },
        P = { class: 'text-xs' },
        L = { key: 2, class: 'mt-3' };
      const T = {
        __name: 'Cards',
        setup: function (e) {
          var t = (0, i.qt)(),
            l = (0, o.qj)({
              data: t.props.quotes || [],
              loader: !1,
              searching: !1,
              pages: {},
              queries: {},
            });
          return function (e, t) {
            var d = (0, n.up)('x-button'),
              T = (0, n.up)('x-divider'),
              R = (0, n.up)('x-input'),
              N = (0, n.up)('x-spinner'),
              I = (0, n.up)('x-icon');
            return (
              (0, n.wg)(),
              (0, n.iD)('div', null, [
                (0, n.Wm)((0, o.SU)(i.Fb), {
                  title: 'Health List ~ Card View',
                }),
                (0, n._)('div', p, [
                  f,
                  (0, n._)('div', _, [
                    (0, n.Wm)(
                      (0, o.SU)(i.rU),
                      { href: '/quotes/health' },
                      {
                        default: (0, n.w5)(function () {
                          return [
                            (0, n.Wm)(
                              d,
                              { size: 'sm', color: '#1d83bc' },
                              {
                                default: (0, n.w5)(function () {
                                  return [(0, n.Uk)(' List View ')];
                                }),
                                _: 1,
                              },
                            ),
                          ];
                        }),
                        _: 1,
                      },
                    ),
                    (0, n.Wm)(
                      (0, o.SU)(i.rU),
                      { href: '/quotes/health/create' },
                      {
                        default: (0, n.w5)(function () {
                          return [
                            (0, n.Wm)(
                              d,
                              { size: 'sm', color: '#ff5e00', tag: 'div' },
                              {
                                default: (0, n.w5)(function () {
                                  return [(0, n.Uk)(' Create Lead ')];
                                }),
                                _: 1,
                              },
                            ),
                          ];
                        }),
                        _: 1,
                      },
                    ),
                  ]),
                ]),
                (0, n.Wm)(T, { class: 'my-4' }),
                l.data.length > 0
                  ? ((0, n.wg)(),
                    (0, n.iD)('div', v, [
                      ((0, n.wg)(!0),
                      (0, n.iD)(
                        n.HY,
                        null,
                        (0, n.Ko)(l.data, function (e) {
                          return (
                            (0, n.wg)(),
                            (0, n.iD)(
                              'div',
                              {
                                key: e.id,
                                class:
                                  'flex flex-col flex-shrink-0 w-64 bg-gray-200 border border-gray-300',
                              },
                              [
                                (0, n._)('div', g, [
                                  (0, n._)('h4', w, (0, a.zw)(e.title), 1),
                                  (0, n._)('div', h, [
                                    b,
                                    (0, n._)(
                                      'span',
                                      null,
                                      (0, a.zw)(e.data.total_leads),
                                      1,
                                    ),
                                  ]),
                                  (0, n._)('div', y, [
                                    x,
                                    (0, n._)(
                                      'span',
                                      null,
                                      (0, a.zw)(
                                        Number(
                                          e.data.total_premium,
                                        ).toLocaleString(),
                                      ),
                                      1,
                                    ),
                                  ]),
                                  (0, n._)('div', null, [
                                    (0, n.Wm)(
                                      R,
                                      {
                                        modelValue: l.queries[e.id],
                                        'onUpdate:modelValue': function (t) {
                                          return (l.queries[e.id] = t);
                                        },
                                        type: 'search',
                                        size: 'xs',
                                        class: 'w-full',
                                        placeholder: 'Search',
                                        onChange: (0, r.iM)(
                                          function (t) {
                                            return (
                                              (n = e.id),
                                              (l.searching = !0),
                                              void (l.queries[n] &&
                                              '' !== l.queries[n] &&
                                              null !== l.queries[n]
                                                ? s.Z.post(
                                                    '/quotes/records/search?term='
                                                      .concat(
                                                        l.queries[n],
                                                        '&status=',
                                                      )
                                                      .concat(
                                                        n,
                                                        '&modelType=Health',
                                                      ),
                                                  )
                                                    .then(function (e) {
                                                      var t = e.data;
                                                      l.data = l.data.map(
                                                        function (e) {
                                                          return (
                                                            e.id === n &&
                                                              (e.data.leads_list =
                                                                c(
                                                                  c(
                                                                    {},
                                                                    t.leads_list,
                                                                  ),
                                                                  {},
                                                                  {
                                                                    next_page_url:
                                                                      null,
                                                                    data: t.leads_list,
                                                                  },
                                                                )),
                                                            e
                                                          );
                                                        },
                                                      );
                                                    })
                                                    .catch(function (e) {
                                                      console.log(e);
                                                    })
                                                    .finally(function () {
                                                      l.searching = !1;
                                                    })
                                                : s.Z.post(
                                                    '/quotes/records?page=1&modelType=Health&status='.concat(
                                                      n,
                                                    ),
                                                  )
                                                    .then(function (e) {
                                                      var t = e.data;
                                                      l.data = l.data.map(
                                                        function (e) {
                                                          return (
                                                            e.id === n &&
                                                              (e.data.leads_list =
                                                                t.leads_list),
                                                            e
                                                          );
                                                        },
                                                      );
                                                    })
                                                    .catch(function (e) {
                                                      console.log(e);
                                                    })
                                                    .finally(function () {
                                                      l.searching = !1;
                                                    }))
                                            );
                                            var n;
                                          },
                                          ['prevent'],
                                        ),
                                        disabled: l.searching,
                                      },
                                      null,
                                      8,
                                      [
                                        'modelValue',
                                        'onUpdate:modelValue',
                                        'onChange',
                                        'disabled',
                                      ],
                                    ),
                                  ]),
                                ]),
                                (0, n._)('div', U, [
                                  l.queries[e.id] && l.searching
                                    ? ((0, n.wg)(),
                                      (0, n.iD)('div', S, [
                                        (0, n.Wm)(N, {
                                          class: 'text-primary-500',
                                        }),
                                      ]))
                                    : (0, n.kq)('', !0),
                                  0 == e.data.leads_list.data &&
                                  e.data.total_leads > 0
                                    ? ((0, n.wg)(),
                                      (0, n.iD)('div', V, [
                                        (0, n.Wm)(I, {
                                          icon: 'box',
                                          class: 'text-secondary-600 mb-2',
                                        }),
                                        k,
                                      ]))
                                    : (0, n.kq)('', !0),
                                  ((0, n.wg)(!0),
                                  (0, n.iD)(
                                    n.HY,
                                    null,
                                    (0, n.Ko)(
                                      e.data.leads_list.data,
                                      function (e) {
                                        var t,
                                          l = e.id,
                                          o = e.uuid,
                                          r = e.code,
                                          i = e.first_name,
                                          s = e.last_name,
                                          d = e.premium,
                                          c = e.updated_at,
                                          m = e.company_name;
                                        return (
                                          (0, n.wg)(),
                                          (0, n.iD)(
                                            'a',
                                            {
                                              key: l,
                                              href: '/quotes/health/'.concat(o),
                                              target: '_blank',
                                              title: 'View Lead',
                                              class:
                                                'block p-3 mt-2 border border-gray-300 bg-white space-y-2 hover:transition hover:border-primary-500 rounded',
                                            },
                                            [
                                              (0, n._)(
                                                'div',
                                                C,
                                                (0, a.zw)(r),
                                                1,
                                              ),
                                              (0, n._)('div', W, [
                                                (0, n.Wm)(I, {
                                                  icon: 'person',
                                                  size: 'sm',
                                                  class: 'text-primary-400',
                                                }),
                                                (0, n._)(
                                                  'p',
                                                  z,
                                                  (0, a.zw)(i) +
                                                    ' ' +
                                                    (0, a.zw)(s),
                                                  1,
                                                ),
                                              ]),
                                              m
                                                ? ((0, n.wg)(),
                                                  (0, n.iD)('div', E, [
                                                    (0, n.Wm)(I, {
                                                      icon: 'company',
                                                      size: 'sm',
                                                      class: 'text-primary-400',
                                                    }),
                                                    (0, n._)(
                                                      'p',
                                                      D,
                                                      (0, a.zw)(m),
                                                      1,
                                                    ),
                                                  ]))
                                                : (0, n.kq)('', !0),
                                              (0, n._)('div', A, [
                                                (0, n.Wm)(I, {
                                                  icon: 'money',
                                                  size: 'sm',
                                                  class: 'text-primary-400',
                                                }),
                                                (0, n._)(
                                                  'p',
                                                  M,
                                                  (0, a.zw)(
                                                    Number(d).toLocaleString(),
                                                  ),
                                                  1,
                                                ),
                                              ]),
                                              (0, n._)('div', O, [
                                                (0, n.Wm)(I, {
                                                  icon: 'calendar',
                                                  size: 'sm',
                                                  class: 'text-primary-400',
                                                }),
                                                (0, n._)(
                                                  'p',
                                                  P,
                                                  (0, a.zw)(
                                                    ((t = c),
                                                    (0, u.WJ)(
                                                      t,
                                                      'DD-MM-YYYY HH:mm:ss',
                                                    ).value),
                                                  ),
                                                  1,
                                                ),
                                              ]),
                                            ],
                                            8,
                                            q,
                                          )
                                        );
                                      },
                                    ),
                                    128,
                                  )),
                                  e.data.total_leads > 0 &&
                                  null !== e.data.leads_list.next_page_url
                                    ? ((0, n.wg)(),
                                      (0, n.iD)('div', L, [
                                        (0, n.Wm)(
                                          d,
                                          {
                                            size: 'xs',
                                            color: '#1d83bc',
                                            class: 'w-full',
                                            outlined: '',
                                            onClick: (0, r.iM)(
                                              function (t) {
                                                return (
                                                  (n = e.id),
                                                  (l.loader = !0),
                                                  (l.pages = c(
                                                    c({}, l.pages),
                                                    {},
                                                    m(
                                                      {},
                                                      n,
                                                      l.pages[n]
                                                        ? Number(l.pages[n]) + 1
                                                        : 2,
                                                    ),
                                                  )),
                                                  void s.Z.post(
                                                    '/quotes/records?page='
                                                      .concat(
                                                        l.pages[n],
                                                        '&modelType=Health&status=',
                                                      )
                                                      .concat(n),
                                                  )
                                                    .then(function (e) {
                                                      var t = e.data;
                                                      l.data = l.data.map(
                                                        function (e) {
                                                          return (
                                                            e.id === n &&
                                                              (e.data.leads_list =
                                                                c(
                                                                  c(
                                                                    {},
                                                                    t.leads_list,
                                                                  ),
                                                                  {},
                                                                  {
                                                                    data: e.data.leads_list.data.concat(
                                                                      t
                                                                        .leads_list
                                                                        .data,
                                                                    ),
                                                                  },
                                                                )),
                                                            e
                                                          );
                                                        },
                                                      );
                                                    })
                                                    .catch(function (e) {
                                                      console.log(e);
                                                    })
                                                    .finally(function () {
                                                      l.loader = !1;
                                                    })
                                                );
                                                var n;
                                              },
                                              ['prevent'],
                                            ),
                                            disabled: l.loader,
                                            loading: l.loader,
                                          },
                                          {
                                            default: (0, n.w5)(function () {
                                              return [(0, n.Uk)(' Load More ')];
                                            }),
                                            _: 2,
                                          },
                                          1032,
                                          ['onClick', 'disabled', 'loading'],
                                        ),
                                      ]))
                                    : (0, n.kq)('', !0),
                                ]),
                              ],
                            )
                          );
                        }),
                        128,
                      )),
                    ]))
                  : (0, n.kq)('', !0),
              ])
            );
          };
        },
      };
    },
    1074: (e, t, l) => {
      'use strict';
      l.r(t), l.d(t, { default: () => m });
      var n = l(6252),
        o = l(2610),
        a = l(9285),
        r = l(2726),
        i = { class: 'flex justify-between items-center' },
        u = (0, n._)(
          'h2',
          { class: 'text-xl font-semibold' },
          'Create Health',
          -1,
        ),
        s = { class: 'grid sm:grid-cols-2 gap-4' },
        d = { class: 'grid grid-cols-2 gap-2' },
        c = { class: 'flex justify-end gap-3 mb-4' };
      const m = {
        __name: 'Create',
        props: { dropdownSource: Object, model: String, genderOptions: Object },
        setup: function (e) {
          var t = e,
            l = (0, n.Fl)(function () {
              return Object.keys(t.genderOptions).map(function (e) {
                return { value: e, label: t.genderOptions[e] };
              });
            }),
            m = (0, a.cI)({
              modelType: '"Health"',
              model: t.model,
              first_name: '',
              last_name: '',
              email: '',
              mobile_no: '',
              dob: '',
              premium: null,
              policy_number: null,
              preference: '',
              details: '',
              marital_status_id: null,
              cover_for_id: null,
              nationality_id: null,
              lead_type_id: null,
              emirate_of_your_visa_id: null,
              salary_band_id: null,
              member_category_id: null,
              gender: null,
              currently_insured_with_id: null,
              policy_start_date: null,
              is_ebp_renewal: null,
              is_ecommerce: null,
              has_dental: null,
              has_worldwide_cover: null,
              has_home: null,
            }),
            p = {
              isEmail: function (e) {
                return (
                  /^\w+([.-]?\w+)*@\w+([.-]?\w+)*(\.\w{2,3})+$/.test(e) ||
                  'E-mail must be valid'
                );
              },
              isRequired: function (e) {
                return !!e || 'This field is required';
              },
            },
            f = (0, o.iH)(!1);
          function _(e) {
            null == m.nationality_id ? (f.value = !0) : (f.value = !1),
              e &&
                m.post('/quotes/save', {
                  onError: function (e) {
                    console.log(e);
                  },
                  onSuccess: function () {
                    a.Nd.get('/quotes/health/');
                  },
                });
          }
          return function (t, v) {
            var g = (0, n.up)('x-button'),
              w = (0, n.up)('x-divider'),
              h = (0, n.up)('x-input'),
              b = (0, n.up)('x-select'),
              y = (0, n.up)('x-checkbox'),
              x = (0, n.up)('x-form');
            return (
              (0, n.wg)(),
              (0, n.iD)('div', null, [
                (0, n.Wm)((0, o.SU)(a.Fb), { title: 'Create Health' }),
                (0, n._)('div', i, [
                  u,
                  (0, n._)('div', null, [
                    (0, n.Wm)(
                      (0, o.SU)(a.rU),
                      { href: '/quotes/health' },
                      {
                        default: (0, n.w5)(function () {
                          return [
                            (0, n.Wm)(
                              g,
                              { size: 'sm', color: '#ff5e00', tag: 'div' },
                              {
                                default: (0, n.w5)(function () {
                                  return [(0, n.Uk)(' Health List ')];
                                }),
                                _: 1,
                              },
                            ),
                          ];
                        }),
                        _: 1,
                      },
                    ),
                  ]),
                ]),
                (0, n.Wm)(w, { class: 'my-4' }),
                (0, n.Wm)(
                  x,
                  { onSubmit: _, 'auto-focus': !1 },
                  {
                    default: (0, n.w5)(function () {
                      return [
                        (0, n._)('div', s, [
                          (0, n.Wm)(
                            h,
                            {
                              modelValue: (0, o.SU)(m).first_name,
                              'onUpdate:modelValue':
                                v[0] ||
                                (v[0] = function (e) {
                                  return ((0, o.SU)(m).first_name = e);
                                }),
                              type: 'text',
                              label: 'FIRST NAME',
                              rules: [p.isRequired],
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue', 'rules'],
                          ),
                          (0, n.Wm)(
                            h,
                            {
                              modelValue: (0, o.SU)(m).last_name,
                              'onUpdate:modelValue':
                                v[1] ||
                                (v[1] = function (e) {
                                  return ((0, o.SU)(m).last_name = e);
                                }),
                              type: 'text',
                              label: 'LAST NAME',
                              rules: [p.isRequired],
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue', 'rules'],
                          ),
                          (0, n.Wm)(
                            h,
                            {
                              modelValue: (0, o.SU)(m).email,
                              'onUpdate:modelValue':
                                v[2] ||
                                (v[2] = function (e) {
                                  return ((0, o.SU)(m).email = e);
                                }),
                              type: 'email',
                              label: 'EMAIL',
                              rules: [p.isRequired],
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue', 'rules'],
                          ),
                          (0, n.Wm)(
                            h,
                            {
                              modelValue: (0, o.SU)(m).mobile_no,
                              'onUpdate:modelValue':
                                v[3] ||
                                (v[3] = function (e) {
                                  return ((0, o.SU)(m).mobile_no = e);
                                }),
                              type: 'tel',
                              label: 'MOBILE NUMBER',
                              rules: [p.isRequired],
                              class: 'w-full',
                              error: (0, o.SU)(m).errors.mobile_no,
                            },
                            null,
                            8,
                            ['modelValue', 'rules', 'error'],
                          ),
                          (0, n.Wm)(
                            h,
                            {
                              modelValue: (0, o.SU)(m).dob,
                              'onUpdate:modelValue':
                                v[4] ||
                                (v[4] = function (e) {
                                  return ((0, o.SU)(m).dob = e);
                                }),
                              type: 'date',
                              label: 'DATE OF BIRTH',
                              rules: [p.isRequired],
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue', 'rules'],
                          ),
                          (0, n.Wm)(
                            h,
                            {
                              modelValue: (0, o.SU)(m).premium,
                              'onUpdate:modelValue':
                                v[5] ||
                                (v[5] = function (e) {
                                  return ((0, o.SU)(m).premium = e);
                                }),
                              type: 'text',
                              label: 'PREMIUM',
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue'],
                          ),
                          (0, n.Wm)(
                            h,
                            {
                              modelValue: (0, o.SU)(m).policy_number,
                              'onUpdate:modelValue':
                                v[6] ||
                                (v[6] = function (e) {
                                  return ((0, o.SU)(m).policy_number = e);
                                }),
                              type: 'text',
                              label: 'POLICY NUMBER',
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue'],
                          ),
                          (0, n.Wm)(
                            b,
                            {
                              modelValue: (0, o.SU)(m).cover_for_id,
                              'onUpdate:modelValue':
                                v[7] ||
                                (v[7] = function (e) {
                                  return ((0, o.SU)(m).cover_for_id = e);
                                }),
                              label: 'WHO WOULD YOU LIKE COVER FOR?',
                              rules: [p.isRequired],
                              options: e.dropdownSource.cover_for_id.map(
                                function (e) {
                                  return { value: e.id, label: e.text };
                                },
                              ),
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue', 'rules', 'options'],
                          ),
                          (0, n.Wm)(
                            h,
                            {
                              modelValue: (0, o.SU)(m).preference,
                              'onUpdate:modelValue':
                                v[8] ||
                                (v[8] = function (e) {
                                  return ((0, o.SU)(m).preference = e);
                                }),
                              type: 'text',
                              label: 'PREFERENCE',
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue'],
                          ),
                          (0, n.Wm)(
                            h,
                            {
                              modelValue: (0, o.SU)(m).details,
                              'onUpdate:modelValue':
                                v[9] ||
                                (v[9] = function (e) {
                                  return ((0, o.SU)(m).details = e);
                                }),
                              type: 'text',
                              label: 'DETAILS',
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue'],
                          ),
                          (0, n.Wm)(
                            b,
                            {
                              modelValue: (0, o.SU)(m).lead_type_id,
                              'onUpdate:modelValue':
                                v[10] ||
                                (v[10] = function (e) {
                                  return ((0, o.SU)(m).lead_type_id = e);
                                }),
                              label: 'LEAD TYPE',
                              options: e.dropdownSource.lead_type_id.map(
                                function (e) {
                                  return { value: e.id, label: e.text };
                                },
                              ),
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue', 'options'],
                          ),
                          (0, n.Wm)(
                            b,
                            {
                              modelValue: (0, o.SU)(m)
                                .currently_insured_with_id,
                              'onUpdate:modelValue':
                                v[11] ||
                                (v[11] = function (e) {
                                  return ((0, o.SU)(
                                    m,
                                  ).currently_insured_with_id = e);
                                }),
                              label: 'CURRENTLY INSURED WITH',
                              options:
                                e.dropdownSource.currently_insured_with_id.map(
                                  function (e) {
                                    return { value: e.id, label: e.text };
                                  },
                                ),
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue', 'options'],
                          ),
                          (0, n.Wm)(
                            b,
                            {
                              modelValue: (0, o.SU)(m).marital_status_id,
                              'onUpdate:modelValue':
                                v[12] ||
                                (v[12] = function (e) {
                                  return ((0, o.SU)(m).marital_status_id = e);
                                }),
                              label: 'MARITAL STATUS',
                              options: e.dropdownSource.marital_status_id.map(
                                function (e) {
                                  return { value: e.id, label: e.text };
                                },
                              ),
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue', 'options'],
                          ),
                          (0, n.Wm)(
                            r.Z,
                            {
                              modelValue: (0, o.SU)(m).nationality_id,
                              'onUpdate:modelValue':
                                v[13] ||
                                (v[13] = function (e) {
                                  return ((0, o.SU)(m).nationality_id = e);
                                }),
                              label: 'NATIONALITY',
                              single: !0,
                              options: e.dropdownSource.nationality_id.map(
                                function (e) {
                                  return { value: e.id, label: e.text };
                                },
                              ),
                              hasError: f.value,
                            },
                            null,
                            8,
                            ['modelValue', 'options', 'hasError'],
                          ),
                          (0, n.Wm)(
                            b,
                            {
                              modelValue: (0, o.SU)(m).emirate_of_your_visa_id,
                              'onUpdate:modelValue':
                                v[14] ||
                                (v[14] = function (e) {
                                  return ((0, o.SU)(m).emirate_of_your_visa_id =
                                    e);
                                }),
                              label: 'EMIRATE OF YOUR VISA',
                              rules: [p.isRequired],
                              options:
                                e.dropdownSource.emirate_of_your_visa_id.map(
                                  function (e) {
                                    return { value: e.id, label: e.text };
                                  },
                                ),
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue', 'rules', 'options'],
                          ),
                          (0, n.Wm)(
                            b,
                            {
                              modelValue: (0, o.SU)(m).member_category_id,
                              'onUpdate:modelValue':
                                v[15] ||
                                (v[15] = function (e) {
                                  return ((0, o.SU)(m).member_category_id = e);
                                }),
                              label: 'MEMBER CATEGORY',
                              options: e.dropdownSource.member_category_id.map(
                                function (e) {
                                  return { value: e.id, label: e.text };
                                },
                              ),
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue', 'options'],
                          ),
                          (0, n.Wm)(
                            b,
                            {
                              modelValue: (0, o.SU)(m).salary_band_id,
                              'onUpdate:modelValue':
                                v[16] ||
                                (v[16] = function (e) {
                                  return ((0, o.SU)(m).salary_band_id = e);
                                }),
                              label: 'SALARY BAND',
                              options: e.dropdownSource.salary_band_id.map(
                                function (e) {
                                  return { value: e.id, label: e.text };
                                },
                              ),
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue', 'options'],
                          ),
                          (0, n.Wm)(
                            b,
                            {
                              modelValue: (0, o.SU)(m).gender,
                              'onUpdate:modelValue':
                                v[17] ||
                                (v[17] = function (e) {
                                  return ((0, o.SU)(m).gender = e);
                                }),
                              label: 'GENDER',
                              options: (0, o.SU)(l),
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue', 'options'],
                          ),
                          (0, n.Wm)(
                            h,
                            {
                              modelValue: (0, o.SU)(m).policy_start_date,
                              'onUpdate:modelValue':
                                v[18] ||
                                (v[18] = function (e) {
                                  return ((0, o.SU)(m).policy_start_date = e);
                                }),
                              type: 'text',
                              label: 'POLICY START DATE',
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue'],
                          ),
                          (0, n._)('div', d, [
                            (0, n.Wm)(
                              y,
                              {
                                modelValue: (0, o.SU)(m).is_ebp_renewal,
                                'onUpdate:modelValue':
                                  v[19] ||
                                  (v[19] = function (e) {
                                    return ((0, o.SU)(m).is_ebp_renewal = e);
                                  }),
                                label: 'IS EBP RENEWAL',
                                color: 'primary',
                                class: 'w-full',
                              },
                              null,
                              8,
                              ['modelValue'],
                            ),
                            (0, n.Wm)(
                              y,
                              {
                                modelValue: (0, o.SU)(m).has_dental,
                                'onUpdate:modelValue':
                                  v[20] ||
                                  (v[20] = function (e) {
                                    return ((0, o.SU)(m).has_dental = e);
                                  }),
                                label: 'DENTAL',
                                color: 'primary',
                              },
                              null,
                              8,
                              ['modelValue'],
                            ),
                            (0, n.Wm)(
                              y,
                              {
                                modelValue: (0, o.SU)(m).has_worldwide_cover,
                                'onUpdate:modelValue':
                                  v[21] ||
                                  (v[21] = function (e) {
                                    return ((0, o.SU)(m).has_worldwide_cover =
                                      e);
                                  }),
                                label: 'WORLDWIDE COVER',
                                color: 'primary',
                              },
                              null,
                              8,
                              ['modelValue'],
                            ),
                            (0, n.Wm)(
                              y,
                              {
                                modelValue: (0, o.SU)(m).has_home,
                                'onUpdate:modelValue':
                                  v[22] ||
                                  (v[22] = function (e) {
                                    return ((0, o.SU)(m).has_home = e);
                                  }),
                                label: 'HOME COUNTRY COVER',
                                color: 'primary',
                              },
                              null,
                              8,
                              ['modelValue'],
                            ),
                          ]),
                        ]),
                        (0, n.Wm)(w, { class: 'my-4' }),
                        (0, n._)('div', c, [
                          (0, n.Wm)(
                            g,
                            {
                              size: 'md',
                              color: 'emerald',
                              type: 'submit',
                              loading: (0, o.SU)(m).processing,
                            },
                            {
                              default: (0, n.w5)(function () {
                                return [(0, n.Uk)(' Create ')];
                              }),
                              _: 1,
                            },
                            8,
                            ['loading'],
                          ),
                        ]),
                      ];
                    }),
                    _: 1,
                  },
                ),
              ])
            );
          };
        },
      };
    },
    8477: (e, t, l) => {
      'use strict';
      l.r(t), l.d(t, { default: () => p });
      var n = l(6252),
        o = l(2610),
        a = l(9285),
        r = l(2726),
        i = { class: 'flex justify-between items-center' },
        u = (0, n._)(
          'h2',
          { class: 'text-xl font-semibold' },
          'Edit Health',
          -1,
        ),
        s = { class: 'space-x-4' },
        d = { class: 'grid sm:grid-cols-2 gap-4' },
        c = { class: 'grid grid-cols-2 gap-2' },
        m = { class: 'flex justify-end gap-3 mb-4' };
      const p = {
        __name: 'Edit',
        props: {
          quote: Object,
          dropdownSource: Object,
          model: String,
          genderOptions: Object,
        },
        setup: function (e) {
          var t = e,
            l = (0, n.Fl)(function () {
              return Object.keys(t.genderOptions).map(function (e) {
                return { value: e, label: t.genderOptions[e] };
              });
            }),
            p = (0, a.cI)({
              modelType: '"Health"',
              model: t.model,
              first_name: t.quote.first_name,
              last_name: t.quote.last_name,
              email: t.quote.email,
              mobile_no: t.quote.mobile_no,
              dob: t.quote.dob
                ? t.quote.dob.split('-').reverse().join('-')
                : null,
              premium: t.quote.premium,
              policy_number: t.quote.policy_number,
              preference: t.quote.preference,
              details: t.quote.details,
              marital_status_id: t.quote.marital_status_id,
              cover_for_id: t.quote.cover_for_id,
              nationality_id: t.quote.nationality_id,
              lead_type_id: t.quote.lead_type_id,
              emirate_of_your_visa_id: t.quote.emirate_of_your_visa_id,
              salary_band_id: t.quote.salary_band_id,
              member_category_id: t.quote.member_category_id,
              gender: t.quote.gender,
              currently_insured_with_id: t.quote.currently_insured_with_id,
              policy_start_date: t.quote.policy_start_date,
              is_ebp_renewal: t.quote.is_ebp_renewal,
              is_ecommerce: t.quote.is_ecommerce,
              has_dental: t.quote.has_dental,
              has_worldwide_cover: t.quote.has_worldwide_cover,
              has_home: t.quote.has_home,
            }),
            f = {
              isEmail: function (e) {
                return (
                  /^\w+([.-]?\w+)*@\w+([.-]?\w+)*(\.\w{2,3})+$/.test(e) ||
                  'E-mail must be valid'
                );
              },
              isRequired: function (e) {
                return !!e || 'This field is required';
              },
            },
            _ = (0, o.iH)(!1);
          function v(e) {
            null == p.nationality_id ? (_.value = !0) : (_.value = !1),
              e &&
                p.put('/quotes/health/'.concat(t.quote.uuid), {
                  onSuccess: function () {
                    a.Nd.get('/quotes/health/'.concat(t.quote.uuid));
                  },
                });
          }
          return function (g, w) {
            var h = (0, n.up)('x-button'),
              b = (0, n.up)('x-divider'),
              y = (0, n.up)('x-input'),
              x = (0, n.up)('x-select'),
              U = (0, n.up)('x-checkbox'),
              S = (0, n.up)('x-form');
            return (
              (0, n.wg)(),
              (0, n.iD)('div', null, [
                (0, n.Wm)((0, o.SU)(a.Fb), { title: 'Edit Health' }),
                (0, n._)('div', i, [
                  u,
                  (0, n._)('div', s, [
                    (0, n.Wm)(
                      (0, o.SU)(a.rU),
                      { href: '/quotes/health/'.concat(t.quote.uuid) },
                      {
                        default: (0, n.w5)(function () {
                          return [
                            (0, n.Wm)(
                              h,
                              { size: 'sm', tag: 'div' },
                              {
                                default: (0, n.w5)(function () {
                                  return [(0, n.Uk)(' View ')];
                                }),
                                _: 1,
                              },
                            ),
                          ];
                        }),
                        _: 1,
                      },
                      8,
                      ['href'],
                    ),
                    (0, n.Wm)(
                      (0, o.SU)(a.rU),
                      { href: '/quotes/health' },
                      {
                        default: (0, n.w5)(function () {
                          return [
                            (0, n.Wm)(
                              h,
                              { size: 'sm', color: '#ff5e00', tag: 'div' },
                              {
                                default: (0, n.w5)(function () {
                                  return [(0, n.Uk)(' Health List ')];
                                }),
                                _: 1,
                              },
                            ),
                          ];
                        }),
                        _: 1,
                      },
                    ),
                  ]),
                ]),
                (0, n.Wm)(b, { class: 'my-4' }),
                (0, n.Wm)(
                  S,
                  { onSubmit: v, 'auto-focus': !1 },
                  {
                    default: (0, n.w5)(function () {
                      return [
                        (0, n._)('div', d, [
                          (0, n.Wm)(
                            y,
                            {
                              modelValue: (0, o.SU)(p).first_name,
                              'onUpdate:modelValue':
                                w[0] ||
                                (w[0] = function (e) {
                                  return ((0, o.SU)(p).first_name = e);
                                }),
                              type: 'text',
                              label: 'FIRST NAME',
                              rules: [f.isRequired],
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue', 'rules'],
                          ),
                          (0, n.Wm)(
                            y,
                            {
                              modelValue: (0, o.SU)(p).last_name,
                              'onUpdate:modelValue':
                                w[1] ||
                                (w[1] = function (e) {
                                  return ((0, o.SU)(p).last_name = e);
                                }),
                              type: 'text',
                              label: 'LAST NAME',
                              rules: [f.isRequired],
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue', 'rules'],
                          ),
                          (0, n.Wm)(
                            y,
                            {
                              modelValue: (0, o.SU)(p).email,
                              'onUpdate:modelValue':
                                w[2] ||
                                (w[2] = function (e) {
                                  return ((0, o.SU)(p).email = e);
                                }),
                              type: 'email',
                              label: 'EMAIL',
                              disabled: !0,
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue'],
                          ),
                          (0, n.Wm)(
                            y,
                            {
                              modelValue: (0, o.SU)(p).mobile_no,
                              'onUpdate:modelValue':
                                w[3] ||
                                (w[3] = function (e) {
                                  return ((0, o.SU)(p).mobile_no = e);
                                }),
                              type: 'tel',
                              label: 'MOBILE NUMBER',
                              disabled: !0,
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue'],
                          ),
                          (0, n.Wm)(
                            y,
                            {
                              modelValue: (0, o.SU)(p).dob,
                              'onUpdate:modelValue':
                                w[4] ||
                                (w[4] = function (e) {
                                  return ((0, o.SU)(p).dob = e);
                                }),
                              type: 'date',
                              label: 'DATE OF BIRTH',
                              rules: [f.isRequired],
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue', 'rules'],
                          ),
                          (0, n.Wm)(
                            y,
                            {
                              modelValue: (0, o.SU)(p).premium,
                              'onUpdate:modelValue':
                                w[5] ||
                                (w[5] = function (e) {
                                  return ((0, o.SU)(p).premium = e);
                                }),
                              type: 'text',
                              label: 'PREMIUM',
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue'],
                          ),
                          (0, n.Wm)(
                            y,
                            {
                              modelValue: (0, o.SU)(p).policy_number,
                              'onUpdate:modelValue':
                                w[6] ||
                                (w[6] = function (e) {
                                  return ((0, o.SU)(p).policy_number = e);
                                }),
                              type: 'text',
                              label: 'POLICY NUMBER',
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue'],
                          ),
                          (0, n.Wm)(
                            x,
                            {
                              modelValue: (0, o.SU)(p).cover_for_id,
                              'onUpdate:modelValue':
                                w[7] ||
                                (w[7] = function (e) {
                                  return ((0, o.SU)(p).cover_for_id = e);
                                }),
                              label: 'WHO WOULD YOU LIKE COVER FOR?',
                              rules: [f.isRequired],
                              options: e.dropdownSource.cover_for_id.map(
                                function (e) {
                                  return { value: e.id, label: e.text };
                                },
                              ),
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue', 'rules', 'options'],
                          ),
                          (0, n.Wm)(
                            y,
                            {
                              modelValue: (0, o.SU)(p).preference,
                              'onUpdate:modelValue':
                                w[8] ||
                                (w[8] = function (e) {
                                  return ((0, o.SU)(p).preference = e);
                                }),
                              type: 'text',
                              label: 'PREFERENCE',
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue'],
                          ),
                          (0, n.Wm)(
                            y,
                            {
                              modelValue: (0, o.SU)(p).details,
                              'onUpdate:modelValue':
                                w[9] ||
                                (w[9] = function (e) {
                                  return ((0, o.SU)(p).details = e);
                                }),
                              type: 'text',
                              label: 'DETAILS',
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue'],
                          ),
                          (0, n.Wm)(
                            x,
                            {
                              modelValue: (0, o.SU)(p).lead_type_id,
                              'onUpdate:modelValue':
                                w[10] ||
                                (w[10] = function (e) {
                                  return ((0, o.SU)(p).lead_type_id = e);
                                }),
                              label: 'LEAD TYPE',
                              options: e.dropdownSource.lead_type_id.map(
                                function (e) {
                                  return { value: e.id, label: e.text };
                                },
                              ),
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue', 'options'],
                          ),
                          (0, n.Wm)(
                            x,
                            {
                              modelValue: (0, o.SU)(p)
                                .currently_insured_with_id,
                              'onUpdate:modelValue':
                                w[11] ||
                                (w[11] = function (e) {
                                  return ((0, o.SU)(
                                    p,
                                  ).currently_insured_with_id = e);
                                }),
                              label: 'CURRENTLY INSURED WITH',
                              options:
                                e.dropdownSource.currently_insured_with_id.map(
                                  function (e) {
                                    return { value: e.id, label: e.text };
                                  },
                                ),
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue', 'options'],
                          ),
                          (0, n.Wm)(
                            x,
                            {
                              modelValue: (0, o.SU)(p).marital_status_id,
                              'onUpdate:modelValue':
                                w[12] ||
                                (w[12] = function (e) {
                                  return ((0, o.SU)(p).marital_status_id = e);
                                }),
                              label: 'MARITAL STATUS',
                              options: e.dropdownSource.marital_status_id.map(
                                function (e) {
                                  return { value: e.id, label: e.text };
                                },
                              ),
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue', 'options'],
                          ),
                          (0, n.Wm)(
                            r.Z,
                            {
                              modelValue: (0, o.SU)(p).nationality_id,
                              'onUpdate:modelValue':
                                w[13] ||
                                (w[13] = function (e) {
                                  return ((0, o.SU)(p).nationality_id = e);
                                }),
                              label: 'NATIONALITY',
                              single: !0,
                              options: e.dropdownSource.nationality_id.map(
                                function (e) {
                                  return { value: e.id, label: e.text };
                                },
                              ),
                              hasError: _.value,
                            },
                            null,
                            8,
                            ['modelValue', 'options', 'hasError'],
                          ),
                          (0, n.Wm)(
                            x,
                            {
                              modelValue: (0, o.SU)(p).emirate_of_your_visa_id,
                              'onUpdate:modelValue':
                                w[14] ||
                                (w[14] = function (e) {
                                  return ((0, o.SU)(p).emirate_of_your_visa_id =
                                    e);
                                }),
                              label: 'EMIRATE OF YOUR VISA',
                              rules: [f.isRequired],
                              options:
                                e.dropdownSource.emirate_of_your_visa_id.map(
                                  function (e) {
                                    return { value: e.id, label: e.text };
                                  },
                                ),
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue', 'rules', 'options'],
                          ),
                          (0, n.Wm)(
                            x,
                            {
                              modelValue: (0, o.SU)(p).member_category_id,
                              'onUpdate:modelValue':
                                w[15] ||
                                (w[15] = function (e) {
                                  return ((0, o.SU)(p).member_category_id = e);
                                }),
                              label: 'MEMBER CATEGORY',
                              options: e.dropdownSource.member_category_id.map(
                                function (e) {
                                  return { value: e.id, label: e.text };
                                },
                              ),
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue', 'options'],
                          ),
                          (0, n.Wm)(
                            x,
                            {
                              modelValue: (0, o.SU)(p).salary_band_id,
                              'onUpdate:modelValue':
                                w[16] ||
                                (w[16] = function (e) {
                                  return ((0, o.SU)(p).salary_band_id = e);
                                }),
                              label: 'SALARY BAND',
                              options: e.dropdownSource.salary_band_id.map(
                                function (e) {
                                  return { value: e.id, label: e.text };
                                },
                              ),
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue', 'options'],
                          ),
                          (0, n.Wm)(
                            x,
                            {
                              modelValue: (0, o.SU)(p).gender,
                              'onUpdate:modelValue':
                                w[17] ||
                                (w[17] = function (e) {
                                  return ((0, o.SU)(p).gender = e);
                                }),
                              label: 'GENDER',
                              options: (0, o.SU)(l),
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue', 'options'],
                          ),
                          (0, n.Wm)(
                            y,
                            {
                              modelValue: (0, o.SU)(p).policy_start_date,
                              'onUpdate:modelValue':
                                w[18] ||
                                (w[18] = function (e) {
                                  return ((0, o.SU)(p).policy_start_date = e);
                                }),
                              type: 'text',
                              label: 'POLICY START DATE',
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue'],
                          ),
                          (0, n._)('div', c, [
                            (0, n.Wm)(
                              U,
                              {
                                modelValue: (0, o.SU)(p).is_ebp_renewal,
                                'onUpdate:modelValue':
                                  w[19] ||
                                  (w[19] = function (e) {
                                    return ((0, o.SU)(p).is_ebp_renewal = e);
                                  }),
                                label: 'IS EBP RENEWAL',
                                color: 'primary',
                                class: 'w-full',
                              },
                              null,
                              8,
                              ['modelValue'],
                            ),
                            (0, n.Wm)(
                              U,
                              {
                                modelValue: (0, o.SU)(p).has_dental,
                                'onUpdate:modelValue':
                                  w[20] ||
                                  (w[20] = function (e) {
                                    return ((0, o.SU)(p).has_dental = e);
                                  }),
                                label: 'DENTAL',
                                color: 'primary',
                              },
                              null,
                              8,
                              ['modelValue'],
                            ),
                            (0, n.Wm)(
                              U,
                              {
                                modelValue: (0, o.SU)(p).has_worldwide_cover,
                                'onUpdate:modelValue':
                                  w[21] ||
                                  (w[21] = function (e) {
                                    return ((0, o.SU)(p).has_worldwide_cover =
                                      e);
                                  }),
                                label: 'WORLDWIDE COVER',
                                color: 'primary',
                              },
                              null,
                              8,
                              ['modelValue'],
                            ),
                            (0, n.Wm)(
                              U,
                              {
                                modelValue: (0, o.SU)(p).has_home,
                                'onUpdate:modelValue':
                                  w[22] ||
                                  (w[22] = function (e) {
                                    return ((0, o.SU)(p).has_home = e);
                                  }),
                                label: 'HOME COUNTRY COVER',
                                color: 'primary',
                              },
                              null,
                              8,
                              ['modelValue'],
                            ),
                          ]),
                        ]),
                        (0, n.Wm)(b, { class: 'my-4' }),
                        (0, n._)('div', m, [
                          (0, n.Wm)(
                            h,
                            {
                              size: 'md',
                              color: 'emerald',
                              type: 'submit',
                              loading: (0, o.SU)(p).processing,
                            },
                            {
                              default: (0, n.w5)(function () {
                                return [(0, n.Uk)(' Update ')];
                              }),
                              _: 1,
                            },
                            8,
                            ['loading'],
                          ),
                        ]),
                      ];
                    }),
                    _: 1,
                  },
                ),
              ])
            );
          };
        },
      };
    },
    8450: (e, t, l) => {
      'use strict';
      l.r(t), l.d(t, { default: () => U });
      var n = l(6252),
        o = l(2610),
        a = l(9963),
        r = l(3577),
        i = l(9285),
        u = { class: 'flex justify-between items-center gap-2 py-6' },
        s = { class: 'text-xs lining-nums font-medium' };
      const d = {
        __name: 'Pagination',
        props: { links: { type: Object, required: !0 } },
        setup: function (e) {
          var t = e,
            l = (0, o.iH)(!1);
          return (
            i.Nd.on('start', function (e) {
              l.value = !0;
            }),
            i.Nd.on('finish', function (e) {
              l.value = !1;
            }),
            function (a, d) {
              var c = (0, n.up)('x-button');
              return (
                (0, n.wg)(),
                (0, n.iD)('div', u, [
                  (0, n.Wm)(
                    (0, o.SU)(i.rU),
                    {
                      href: t.links.prev ? t.links.prev : '#',
                      'preserve-scroll': '',
                      'preserve-state': '',
                    },
                    {
                      default: (0, n.w5)(function () {
                        return [
                          (0, n.Wm)(
                            c,
                            {
                              tag: 'div',
                              size: 'sm',
                              'icon-left': 'prev',
                              loading: l.value,
                              disabled: 1 === e.links.current,
                            },
                            {
                              default: (0, n.w5)(function () {
                                return [(0, n.Uk)(' Previous ')];
                              }),
                              _: 1,
                            },
                            8,
                            ['loading', 'disabled'],
                          ),
                        ];
                      }),
                      _: 1,
                    },
                    8,
                    ['href'],
                  ),
                  (0, n._)(
                    'div',
                    s,
                    ' Page ' +
                      (0, r.zw)(e.links.current) +
                      ' ~ [' +
                      (0, r.zw)(e.links.from) +
                      ' - ' +
                      (0, r.zw)(e.links.to) +
                      '] ',
                    1,
                  ),
                  (0, n.Wm)(
                    (0, o.SU)(i.rU),
                    {
                      href: t.links.next ? t.links.next : '#',
                      'preserve-scroll': '',
                      'preserve-state': '',
                    },
                    {
                      default: (0, n.w5)(function () {
                        return [
                          (0, n.Wm)(
                            c,
                            {
                              tag: 'div',
                              size: 'sm',
                              'icon-right': 'next',
                              loading: l.value,
                              disabled: null === t.links.next,
                            },
                            {
                              default: (0, n.w5)(function () {
                                return [(0, n.Uk)(' Next ')];
                              }),
                              _: 1,
                            },
                            8,
                            ['loading', 'disabled'],
                          ),
                        ];
                      }),
                      _: 1,
                    },
                    8,
                    ['href'],
                  ),
                ])
              );
            }
          );
        },
      };
      var c = l(4105);
      const m = {
        name: 'ExportExcel',
        props: {
          columns: { type: Array, default: [] },
          data: { type: Array, default: [] },
          filename: { type: String, default: 'excel' },
          sheetname: { type: String, default: 'SheetName' },
        },
        data: function () {
          return { dadosAux: [] };
        },
        methods: {
          exportExcel: function () {
            var e = this;
            this.$emit('loading', !0);
            var t = [],
              l = [],
              n = [],
              o = this.filename + '.xlsx',
              a = this.sheetname;
            0 !== this.columns.length
              ? 0 !== this.data.length
                ? ((l = this.columns.map(function (e) {
                    return e.text;
                  })),
                  setTimeout(function () {
                    (n = e.data.map(function (t) {
                      var l = [];
                      return (
                        e.columns.forEach(function (e) {
                          e.dataFormat && 'function' == typeof e.dataFormat
                            ? l.push(e.dataFormat(t[e.value]))
                            : l.push(t[e.value]);
                        }),
                        l
                      );
                    })),
                      (t = [l].concat(n));
                    var r = c.ZP.utils.book_new(),
                      i = c.ZP.utils.aoa_to_sheet(t);
                    c.ZP.utils.book_append_sheet(r, i, a),
                      c.ZP.writeFile(r, o),
                      e.$emit('loading', !1);
                  }, 100))
                : this.$emit('error', {
                    message: 'Without data!',
                    error: 'data',
                  })
              : this.$emit('error', {
                  message: 'Without columns!',
                  error: 'column',
                });
          },
        },
      };
      const p = (0, l(3744).Z)(m, [
        [
          'render',
          function (e, t, l, o, a, r) {
            return (
              (0, n.wg)(),
              (0, n.iD)(
                'button',
                {
                  onClick:
                    t[0] ||
                    (t[0] = function () {
                      return r.exportExcel && r.exportExcel.apply(r, arguments);
                    }),
                },
                [(0, n.WI)(e.$slots, 'default')],
              )
            );
          },
        ],
      ]);
      var f = l(2726),
        _ = { class: 'flex justify-between items-center' },
        v = (0, n._)(
          'h2',
          { class: 'text-xl font-semibold' },
          'Health List',
          -1,
        ),
        g = { class: 'space-x-3' },
        w = { class: 'grid sm:grid-cols-2 md:grid-cols-4 gap-4' },
        h = { class: 'flex justify-end gap-3 mb-4' },
        b = { key: 0, class: 'mb-4' },
        y = { class: 'lining-nums' },
        x = { class: 'text-center' };
      const U = {
        __name: 'Index',
        props: { quotes: Object, leadStatuses: Array, advisors: Array },
        setup: function (e) {
          var t = (0, i.qt)(),
            l = (0, o.qj)({ table: !1, export: !1 }),
            u = (0, o.iH)([]),
            s = [
              { text: 'CDB ID', value: 'code' },
              { text: 'FIRST NAME', value: 'first_name' },
              { text: 'LAST NAME', value: 'last_name' },
              { text: 'LEAD STATUS', value: 'quote_status_id_text' },
              { text: 'ADVISOR', value: 'advisor_id_text' },
              { text: 'WC ADVISOR', value: 'wcu_id_text' },
              { text: 'CREATED DATE', value: 'created_at' },
              { text: 'LAST MODIFIED DATE', value: 'updated_at' },
              { text: 'HEALTH TEAM TYPE', value: 'health_team_type' },
              { text: 'TRANSAPP CODE', value: 'transapp_code' },
              { text: 'LOST REASON', value: 'lost_reason' },
              { text: 'PREMIUM', value: 'premium' },
              { text: 'POLICY NUMBER', value: 'policy_number' },
              { text: 'SOURCE', value: 'source' },
              { text: 'LEAD TYPE', value: 'lead_type_id_text' },
              { text: 'SALARY BAND', value: 'salary_band_id_text' },
              { text: 'MEMBER CATEGORY', value: 'member_category_id_text' },
              {
                text: 'CURRENTLY INSURED WITH',
                value: 'currently_insured_with_id_text',
              },
              { text: 'IS ECOMMERCE', value: 'is_ecommerce' },
            ],
            c = (0, o.qj)({
              code: '',
              first_name: '',
              last_name: '',
              email: '',
              mobile_no: '',
              created_at_start: '',
              created_at_end: '',
              sub_team: '',
              quote_status: [],
              advisors: [],
              is_ecommerce: '',
              is_renewal: '',
              page: 1,
            }),
            m = [
              { value: '', label: 'All' },
              { value: 'RM-NB', label: 'RM-NB' },
              { value: 'RM-Speed', label: 'RM-Speed' },
              { value: 'EBP', label: 'EBP' },
              { value: 'Wow-Call', label: 'Wow-Call' },
              { value: 'No-Type', label: 'No-Type' },
            ],
            U = (0, n.Fl)(function () {
              return t.props.leadStatuses.map(function (e) {
                return { value: e.id, label: e.text };
              });
            }),
            S = (0, n.Fl)(function () {
              return t.props.advisors.map(function (e) {
                return { value: e.id, label: e.name };
              });
            });
          function V(e) {
            e
              ? ((c.page = 1),
                Object.keys(c).forEach(function (e) {
                  return ('' === c[e] || 0 === c[e].length) && delete c[e];
                }),
                i.Nd.visit('/quotes/health', {
                  method: 'get',
                  data: c,
                  preserveState: !0,
                  preserveScroll: !0,
                  onBefore: function () {
                    return (l.table = !0);
                  },
                  onFinish: function () {
                    return (l.table = !1);
                  },
                }))
              : console.log('Invalid');
          }
          function k() {
            i.Nd.visit('/quotes/health', {
              method: 'get',
              data: { page: 1 },
              preserveScroll: !0,
              onBefore: function () {
                return (l.table = !0);
              },
              onSuccess: function () {
                return (l.table = !1);
              },
            });
          }
          return (
            (0, n.bv)(function () {
              var e, t;
              (e = window.location.search),
                (t = new URLSearchParams(e)).has('code') &&
                  (c.code = t.get('code')),
                t.has('first_name') && (c.first_name = t.get('first_name')),
                t.has('last_name') && (c.last_name = t.get('last_name')),
                t.has('email') && (c.email = t.get('email')),
                t.has('mobile_no') && (c.mobile_no = t.get('mobile_no')),
                t.has('created_at_start') &&
                  (c.created_at_start = t.get('created_at_start')),
                t.has('created_at_end') &&
                  (c.created_at_end = t.get('created_at_end')),
                t.has('sub_team') && (c.sub_team = t.get('sub_team')),
                t.has('quote_status[]') &&
                  (c.quote_status = t
                    .getAll('quote_status[]')
                    .map(function (e) {
                      return parseInt(e);
                    })),
                t.has('advisors[]') &&
                  (c.advisors = t.getAll('advisors[]').map(function (e) {
                    return parseInt(e);
                  })),
                t.has('is_renewal') && (c.is_renewal = t.get('is_renewal')),
                t.has('is_ecommerce') &&
                  (c.is_ecommerce = t.get('is_ecommerce'));
            }),
            function (t, q) {
              var C = (0, n.up)('x-button'),
                W = (0, n.up)('x-divider'),
                z = (0, n.up)('x-input'),
                E = (0, n.up)('x-select'),
                D = (0, n.up)('x-form'),
                A = (0, n.up)('x-tag'),
                M = (0, n.up)('DataTable');
              return (
                (0, n.wg)(),
                (0, n.iD)('div', null, [
                  (0, n.Wm)((0, o.SU)(i.Fb), { title: 'Health List' }),
                  (0, n._)('div', _, [
                    v,
                    (0, n._)('div', g, [
                      (0, n.Wm)(
                        (0, o.SU)(i.rU),
                        { href: '/quotes/health-cards' },
                        {
                          default: (0, n.w5)(function () {
                            return [
                              (0, n.Wm)(
                                C,
                                { size: 'sm', color: '#1d83bc', tag: 'div' },
                                {
                                  default: (0, n.w5)(function () {
                                    return [(0, n.Uk)(' Cards View ')];
                                  }),
                                  _: 1,
                                },
                              ),
                            ];
                          }),
                          _: 1,
                        },
                      ),
                      (0, n.Wm)(
                        (0, o.SU)(i.rU),
                        { href: '/quotes/health/create' },
                        {
                          default: (0, n.w5)(function () {
                            return [
                              (0, n.Wm)(
                                C,
                                { size: 'sm', color: '#ff5e00', tag: 'div' },
                                {
                                  default: (0, n.w5)(function () {
                                    return [(0, n.Uk)(' Create Lead ')];
                                  }),
                                  _: 1,
                                },
                              ),
                            ];
                          }),
                          _: 1,
                        },
                      ),
                    ]),
                  ]),
                  (0, n.Wm)(W, { class: 'my-4' }),
                  (0, n.Wm)(
                    D,
                    { onSubmit: V, 'auto-focus': !1 },
                    {
                      default: (0, n.w5)(function () {
                        return [
                          (0, n._)('div', w, [
                            (0, n.Wm)(
                              z,
                              {
                                modelValue: c.code,
                                'onUpdate:modelValue':
                                  q[0] ||
                                  (q[0] = function (e) {
                                    return (c.code = e);
                                  }),
                                type: 'search',
                                name: 'code',
                                label: 'CDB ID',
                                class: 'w-full',
                                placeholder: 'Search by CDB ID',
                              },
                              null,
                              8,
                              ['modelValue'],
                            ),
                            (0, n.Wm)(
                              z,
                              {
                                modelValue: c.first_name,
                                'onUpdate:modelValue':
                                  q[1] ||
                                  (q[1] = function (e) {
                                    return (c.first_name = e);
                                  }),
                                type: 'search',
                                name: 'first_name',
                                label: 'First Name',
                                class: 'w-full',
                                placeholder: 'Search by First Name',
                              },
                              null,
                              8,
                              ['modelValue'],
                            ),
                            (0, n.Wm)(
                              z,
                              {
                                modelValue: c.last_name,
                                'onUpdate:modelValue':
                                  q[2] ||
                                  (q[2] = function (e) {
                                    return (c.last_name = e);
                                  }),
                                type: 'search',
                                name: 'last_name',
                                label: 'Last Name',
                                class: 'w-full',
                                placeholder: 'Search by Last Name',
                              },
                              null,
                              8,
                              ['modelValue'],
                            ),
                            (0, n.Wm)(
                              z,
                              {
                                modelValue: c.email,
                                'onUpdate:modelValue':
                                  q[3] ||
                                  (q[3] = function (e) {
                                    return (c.email = e);
                                  }),
                                type: 'search',
                                name: 'email',
                                label: 'Email',
                                class: 'w-full',
                                placeholder: 'Search by Email',
                              },
                              null,
                              8,
                              ['modelValue'],
                            ),
                            (0, n.Wm)(
                              z,
                              {
                                modelValue: c.mobile_no,
                                'onUpdate:modelValue':
                                  q[4] ||
                                  (q[4] = function (e) {
                                    return (c.mobile_no = e);
                                  }),
                                type: 'search',
                                name: 'mobile_no',
                                label: 'Mobile Number',
                                class: 'w-full',
                                placeholder: 'Search by Mobile Number',
                              },
                              null,
                              8,
                              ['modelValue'],
                            ),
                            (0, n.Wm)(
                              z,
                              {
                                modelValue: c.created_at_start,
                                'onUpdate:modelValue':
                                  q[5] ||
                                  (q[5] = function (e) {
                                    return (c.created_at_start = e);
                                  }),
                                type: 'date',
                                name: 'created_at_start',
                                label: 'Created Date',
                                class: 'w-full',
                              },
                              null,
                              8,
                              ['modelValue'],
                            ),
                            (0, n.Wm)(
                              z,
                              {
                                modelValue: c.created_at_end,
                                'onUpdate:modelValue':
                                  q[6] ||
                                  (q[6] = function (e) {
                                    return (c.created_at_end = e);
                                  }),
                                type: 'date',
                                name: 'created_at_end',
                                label: 'Created Date End',
                                class: 'w-full',
                              },
                              null,
                              8,
                              ['modelValue'],
                            ),
                            (0, n.Wm)(
                              E,
                              {
                                modelValue: c.sub_team,
                                'onUpdate:modelValue':
                                  q[7] ||
                                  (q[7] = function (e) {
                                    return (c.sub_team = e);
                                  }),
                                label: 'Sub Team',
                                options: m,
                                placeholder: 'Search by Sub Team',
                                class: 'w-full',
                              },
                              null,
                              8,
                              ['modelValue'],
                            ),
                            (0, n.Wm)(
                              f.Z,
                              {
                                modelValue: c.quote_status,
                                'onUpdate:modelValue':
                                  q[8] ||
                                  (q[8] = function (e) {
                                    return (c.quote_status = e);
                                  }),
                                label: 'Lead Status',
                                name: 'quote_status',
                                placeholder: 'Search by Lead Status',
                                options: (0, o.SU)(U),
                              },
                              null,
                              8,
                              ['modelValue', 'options'],
                            ),
                            (0, n.Wm)(
                              f.Z,
                              {
                                modelValue: c.advisors,
                                'onUpdate:modelValue':
                                  q[9] ||
                                  (q[9] = function (e) {
                                    return (c.advisors = e);
                                  }),
                                label: 'Advisor',
                                placeholder: 'Search by Advisor',
                                options: (0, o.SU)(S),
                              },
                              null,
                              8,
                              ['modelValue', 'options'],
                            ),
                            (0, n.Wm)(
                              E,
                              {
                                modelValue: c.is_ecommerce,
                                'onUpdate:modelValue':
                                  q[10] ||
                                  (q[10] = function (e) {
                                    return (c.is_ecommerce = e);
                                  }),
                                label: 'Is Ecommerce',
                                placeholder: 'Search by Ecommerce',
                                options: [
                                  { value: '', label: 'All' },
                                  { value: 'Yes', label: 'Yes' },
                                  { value: 'No', label: 'No' },
                                ],
                                class: 'w-full',
                              },
                              null,
                              8,
                              ['modelValue'],
                            ),
                            (0, n.Wm)(
                              E,
                              {
                                modelValue: c.is_renewal,
                                'onUpdate:modelValue':
                                  q[11] ||
                                  (q[11] = function (e) {
                                    return (c.is_renewal = e);
                                  }),
                                label: 'Is Renewal',
                                placeholder: 'Search by Renewal',
                                options: [
                                  { value: '', label: 'All' },
                                  { value: 'Yes', label: 'Yes' },
                                  { value: 'No', label: 'No' },
                                ],
                                class: 'w-full',
                              },
                              null,
                              8,
                              ['modelValue'],
                            ),
                          ]),
                          (0, n._)('div', h, [
                            (0, n.Wm)(
                              C,
                              { size: 'sm', color: '#ff5e00', type: 'submit' },
                              {
                                default: (0, n.w5)(function () {
                                  return [(0, n.Uk)('Search')];
                                }),
                                _: 1,
                              },
                            ),
                            (0, n.Wm)(
                              C,
                              {
                                size: 'sm',
                                color: 'primary',
                                onClick: (0, a.iM)(k, ['prevent']),
                              },
                              {
                                default: (0, n.w5)(function () {
                                  return [(0, n.Uk)(' Reset ')];
                                }),
                                _: 1,
                              },
                              8,
                              ['onClick'],
                            ),
                          ]),
                        ];
                      }),
                      _: 1,
                    },
                  ),
                  (0, n.Wm)(
                    a.uT,
                    { name: 'fade' },
                    {
                      default: (0, n.w5)(function () {
                        return [
                          u.value.length > 0
                            ? ((0, n.wg)(),
                              (0, n.iD)('div', b, [
                                (0, n.Wm)(
                                  p,
                                  {
                                    data: u.value,
                                    columns: s,
                                    filename: 'Health-List',
                                    sheetname: 'Leads',
                                  },
                                  {
                                    default: (0, n.w5)(function () {
                                      return [
                                        (0, n.Wm)(
                                          C,
                                          { size: 'sm', color: 'emerald' },
                                          {
                                            default: (0, n.w5)(function () {
                                              return [
                                                (0, n.Uk)(' Export - '),
                                                (0, n._)(
                                                  'span',
                                                  y,
                                                  ' Selected: ' +
                                                    (0, r.zw)(u.value.length),
                                                  1,
                                                ),
                                              ];
                                            }),
                                            _: 1,
                                          },
                                        ),
                                      ];
                                    }),
                                    _: 1,
                                  },
                                  8,
                                  ['data'],
                                ),
                              ]))
                            : (0, n.kq)('', !0),
                        ];
                      }),
                      _: 1,
                    },
                  ),
                  (0, n.Wm)(
                    M,
                    {
                      'items-selected': u.value,
                      'onUpdate:items-selected':
                        q[12] ||
                        (q[12] = function (e) {
                          return (u.value = e);
                        }),
                      'table-class-name': 'tablefixed',
                      loading: l.table,
                      headers: s,
                      items: e.quotes.data || [],
                      'border-cell': '',
                      'hide-rows-per-page': '',
                      'hide-footer': '',
                      'fixed-checkbox': '',
                    },
                    {
                      'item-code': (0, n.w5)(function (e) {
                        var t = e.code,
                          l = e.uuid;
                        return [
                          (0, n.Wm)(
                            (0, o.SU)(i.rU),
                            {
                              href: '/quotes/health/'.concat(l),
                              class: 'text-primary-500 hover:underline',
                            },
                            {
                              default: (0, n.w5)(function () {
                                return [(0, n.Uk)((0, r.zw)(t), 1)];
                              }),
                              _: 2,
                            },
                            1032,
                            ['href'],
                          ),
                        ];
                      }),
                      'item-is_ecommerce': (0, n.w5)(function (e) {
                        var t = e.is_ecommerce;
                        return [
                          (0, n._)('div', x, [
                            (0, n.Wm)(
                              A,
                              { size: 'sm', color: t ? 'success' : 'error' },
                              {
                                default: (0, n.w5)(function () {
                                  return [
                                    (0, n.Uk)((0, r.zw)(t ? 'Yes' : 'No'), 1),
                                  ];
                                }),
                                _: 2,
                              },
                              1032,
                              ['color'],
                            ),
                          ]),
                        ];
                      }),
                      _: 1,
                    },
                    8,
                    ['items-selected', 'loading', 'items'],
                  ),
                  (0, n.Wm)(
                    d,
                    {
                      links: {
                        next: e.quotes.next_page_url,
                        prev: e.quotes.prev_page_url,
                        current: e.quotes.current_page,
                        from: e.quotes.from,
                        to: e.quotes.to,
                      },
                    },
                    null,
                    8,
                    ['links'],
                  ),
                ])
              );
            }
          );
        },
      };
    },
    8876: (e, t, l) => {
      'use strict';
      l.r(t), l.d(t, { default: () => A });
      var n = l(6252),
        o = l(2610),
        a = l(3577),
        r = l(7163),
        i = l(3299),
        u = { class: 'w-full' },
        s = { class: 'grid md:grid-cols-2 gap-x-6 gap-y-4 p-4' },
        d = { class: 'grid sm:grid-cols-2' },
        c = (0, n._)('dt', { class: 'font-medium' }, 'Provider Code', -1),
        m = { class: 'grid sm:grid-cols-2' },
        p = (0, n._)('dt', { class: 'font-medium' }, 'Provider Name', -1),
        f = { class: 'grid sm:grid-cols-2' },
        _ = (0, n._)('dt', { class: 'font-medium' }, 'Actual Premium', -1),
        v = { class: 'grid sm:grid-cols-2' },
        g = (0, n._)('dt', { class: 'font-medium' }, 'Discount Premium', -1),
        w = { class: 'p-4' },
        h = { class: 'grid md:grid-cols-2 gap-5 p-4' },
        b = { class: 'font-medium mb-1' },
        y = { class: 'grid md:grid-cols-2 gap-5 p-4' },
        x = { class: 'font-medium mb-1' },
        U = { class: 'grid md:grid-cols-2 gap-5 p-4' },
        S = { class: 'font-medium mb-1' },
        V = { class: 'grid md:grid-cols-2 gap-5 p-4' },
        k = { class: 'font-medium mb-1' },
        q = { class: 'font-medium mb-1' },
        C = { class: 'grid md:grid-cols-2 gap-5 p-4' },
        W = { class: 'font-medium mb-1' },
        z = { class: 'grid md:grid-cols-2 gap-5 p-4' },
        E = { class: 'font-medium mb-1' },
        D = { class: 'grid md:grid-cols-2 gap-5 p-4' };
      const A = {
        __name: 'AvailablePlans',
        props: { plan: Object },
        setup: function (e) {
          var t = (0, o.iH)([
            { index: 0, label: 'General Info' },
            { index: 1, label: 'Members' },
            { index: 2, label: 'In Patient' },
            { index: 3, label: 'Out Patient' },
            { index: 4, label: 'Co-pay/Co-insurance' },
            { index: 5, label: 'Region coverage & Network list' },
            { index: 6, label: 'Maternity cover' },
            { index: 7, label: 'Exclusions' },
            { index: 8, label: 'Policy Detail' },
          ]);
          return function (l, A) {
            var M = (0, n.up)('x-input'),
              O = (0, n.up)('x-link');
            return (
              (0, n.wg)(),
              (0, n.iD)('div', u, [
                (0, n.Wm)((0, o.SU)(r.v0), null, {
                  default: (0, n.w5)(function () {
                    return [
                      (0, n.Wm)(
                        (0, o.SU)(r.td),
                        {
                          class:
                            'flex flex-row flex-wrap gap-2 rounded-xl bg-slate-100 p-1.5 w-full',
                        },
                        {
                          default: (0, n.w5)(function () {
                            return [
                              ((0, n.wg)(!0),
                              (0, n.iD)(
                                n.HY,
                                null,
                                (0, n.Ko)(t.value, function (e) {
                                  var t = e.index,
                                    l = e.label;
                                  return (
                                    (0, n.wg)(),
                                    (0, n.j4)(
                                      (0, o.SU)(r.OK),
                                      { as: 'template', key: t },
                                      {
                                        default: (0, n.w5)(function (e) {
                                          var t = e.selected;
                                          return [
                                            (0, n._)(
                                              'button',
                                              {
                                                class: (0, a.C_)([
                                                  'rounded-lg px-3 py-2 text-sm font-medium text-gray-800 transition duration-200 ease-in-out uppercase',
                                                  'ring-white ring-opacity-60 ring-offset-2 ring-offset-primary-50 focus:outline-none focus:ring-2',
                                                  t
                                                    ? 'bg-white shadow text-primary-600'
                                                    : 'hover:bg-white/50',
                                                ]),
                                              },
                                              (0, a.zw)(l),
                                              3,
                                            ),
                                          ];
                                        }),
                                        _: 2,
                                      },
                                      1024,
                                    )
                                  );
                                }),
                                128,
                              )),
                            ];
                          }),
                          _: 1,
                        },
                      ),
                      (0, n.Wm)(
                        (0, o.SU)(r.nP),
                        { class: 'mt-2 text-sm min-h-[70vh]' },
                        {
                          default: (0, n.w5)(function () {
                            return [
                              (0, n.Wm)((0, o.SU)(r.x4), null, {
                                default: (0, n.w5)(function () {
                                  return [
                                    (0, n._)('dl', s, [
                                      (0, n._)('div', d, [
                                        c,
                                        (0, n._)(
                                          'dd',
                                          null,
                                          (0, a.zw)(e.plan.code),
                                          1,
                                        ),
                                      ]),
                                      (0, n._)('div', m, [
                                        p,
                                        (0, n._)(
                                          'dd',
                                          null,
                                          (0, a.zw)(e.plan.providerName),
                                          1,
                                        ),
                                      ]),
                                      (0, n._)('div', f, [
                                        _,
                                        (0, n._)(
                                          'dd',
                                          null,
                                          (0, a.zw)(e.plan.actualPremium),
                                          1,
                                        ),
                                      ]),
                                      (0, n._)('div', v, [
                                        g,
                                        (0, n._)(
                                          'dd',
                                          null,
                                          (0, a.zw)(e.plan.discountPremium),
                                          1,
                                        ),
                                      ]),
                                    ]),
                                  ];
                                }),
                                _: 1,
                              }),
                              (0, n.Wm)((0, o.SU)(r.x4), null, {
                                default: (0, n.w5)(function () {
                                  return [
                                    (0, n._)('div', w, [
                                      ((0, n.wg)(!0),
                                      (0, n.iD)(
                                        n.HY,
                                        null,
                                        (0, n.Ko)(
                                          e.plan.memberPremiumBreakdown || [],
                                          function (e) {
                                            return (
                                              (0, n.wg)(),
                                              (0, n.iD)(
                                                'div',
                                                {
                                                  key: e.memberId,
                                                  class:
                                                    'grid grid-cols-2 md:grid-cols-4 gap-2 my-4 border-b',
                                                },
                                                [
                                                  (0, n._)(
                                                    'div',
                                                    null,
                                                    (0, a.zw)(
                                                      e.memberCategoryText,
                                                    ),
                                                    1,
                                                  ),
                                                  (0, n._)(
                                                    'div',
                                                    null,
                                                    (0, a.zw)(
                                                      ((t = e.dob),
                                                      (0, i.WJ)(t, 'DD-MM-YYYY')
                                                        .value),
                                                    ),
                                                    1,
                                                  ),
                                                  (0, n._)(
                                                    'div',
                                                    null,
                                                    (0, a.zw)(e.gender),
                                                    1,
                                                  ),
                                                  (0, n.Wm)(
                                                    M,
                                                    {
                                                      value: e.premium,
                                                      disabled: !0,
                                                      size: 'sm',
                                                    },
                                                    null,
                                                    8,
                                                    ['value'],
                                                  ),
                                                ],
                                              )
                                            );
                                            var t;
                                          },
                                        ),
                                        128,
                                      )),
                                    ]),
                                  ];
                                }),
                                _: 1,
                              }),
                              (0, n.Wm)((0, o.SU)(r.x4), null, {
                                default: (0, n.w5)(function () {
                                  return [
                                    (0, n._)('dl', h, [
                                      ((0, n.wg)(!0),
                                      (0, n.iD)(
                                        n.HY,
                                        null,
                                        (0, n.Ko)(
                                          e.plan.benefits.inpatient || [],
                                          function (e) {
                                            return (
                                              (0, n.wg)(),
                                              (0, n.iD)(
                                                'div',
                                                { key: e.code },
                                                [
                                                  (0, n._)(
                                                    'dt',
                                                    b,
                                                    (0, a.zw)(e.text),
                                                    1,
                                                  ),
                                                  (0, n._)(
                                                    'dd',
                                                    null,
                                                    (0, a.zw)(e.value),
                                                    1,
                                                  ),
                                                ],
                                              )
                                            );
                                          },
                                        ),
                                        128,
                                      )),
                                    ]),
                                  ];
                                }),
                                _: 1,
                              }),
                              (0, n.Wm)((0, o.SU)(r.x4), null, {
                                default: (0, n.w5)(function () {
                                  return [
                                    (0, n._)('dl', y, [
                                      ((0, n.wg)(!0),
                                      (0, n.iD)(
                                        n.HY,
                                        null,
                                        (0, n.Ko)(
                                          e.plan.benefits.outpatient || [],
                                          function (e) {
                                            return (
                                              (0, n.wg)(),
                                              (0, n.iD)(
                                                'div',
                                                { key: e.code },
                                                [
                                                  (0, n._)(
                                                    'dt',
                                                    x,
                                                    (0, a.zw)(e.text),
                                                    1,
                                                  ),
                                                  (0, n._)(
                                                    'dd',
                                                    null,
                                                    (0, a.zw)(e.value),
                                                    1,
                                                  ),
                                                ],
                                              )
                                            );
                                          },
                                        ),
                                        128,
                                      )),
                                    ]),
                                  ];
                                }),
                                _: 1,
                              }),
                              (0, n.Wm)((0, o.SU)(r.x4), null, {
                                default: (0, n.w5)(function () {
                                  return [
                                    (0, n._)('dl', U, [
                                      ((0, n.wg)(!0),
                                      (0, n.iD)(
                                        n.HY,
                                        null,
                                        (0, n.Ko)(
                                          e.plan.benefits.coInsurance || [],
                                          function (e) {
                                            return (
                                              (0, n.wg)(),
                                              (0, n.iD)(
                                                'div',
                                                { key: e.code },
                                                [
                                                  (0, n._)(
                                                    'dt',
                                                    S,
                                                    (0, a.zw)(e.text),
                                                    1,
                                                  ),
                                                  (0, n._)(
                                                    'dd',
                                                    null,
                                                    (0, a.zw)(e.value),
                                                    1,
                                                  ),
                                                ],
                                              )
                                            );
                                          },
                                        ),
                                        128,
                                      )),
                                    ]),
                                  ];
                                }),
                                _: 1,
                              }),
                              (0, n.Wm)((0, o.SU)(r.x4), null, {
                                default: (0, n.w5)(function () {
                                  return [
                                    (0, n._)('dl', V, [
                                      ((0, n.wg)(!0),
                                      (0, n.iD)(
                                        n.HY,
                                        null,
                                        (0, n.Ko)(
                                          e.plan.benefits.regionCover || [],
                                          function (e) {
                                            return (
                                              (0, n.wg)(),
                                              (0, n.iD)(
                                                'div',
                                                { key: e.code },
                                                [
                                                  (0, n._)(
                                                    'dt',
                                                    k,
                                                    (0, a.zw)(e.text),
                                                    1,
                                                  ),
                                                  (0, n._)(
                                                    'dd',
                                                    null,
                                                    (0, a.zw)(e.value),
                                                    1,
                                                  ),
                                                ],
                                              )
                                            );
                                          },
                                        ),
                                        128,
                                      )),
                                      ((0, n.wg)(!0),
                                      (0, n.iD)(
                                        n.HY,
                                        null,
                                        (0, n.Ko)(
                                          e.plan.benefits.networkList || [],
                                          function (e) {
                                            return (
                                              (0, n.wg)(),
                                              (0, n.iD)(
                                                'div',
                                                { key: e.code },
                                                [
                                                  (0, n._)(
                                                    'dt',
                                                    q,
                                                    (0, a.zw)(e.text),
                                                    1,
                                                  ),
                                                  (0, n._)(
                                                    'dd',
                                                    null,
                                                    (0, a.zw)(e.value),
                                                    1,
                                                  ),
                                                ],
                                              )
                                            );
                                          },
                                        ),
                                        128,
                                      )),
                                    ]),
                                  ];
                                }),
                                _: 1,
                              }),
                              (0, n.Wm)((0, o.SU)(r.x4), null, {
                                default: (0, n.w5)(function () {
                                  return [
                                    (0, n._)('dl', C, [
                                      ((0, n.wg)(!0),
                                      (0, n.iD)(
                                        n.HY,
                                        null,
                                        (0, n.Ko)(
                                          e.plan.benefits.maternityCover || [],
                                          function (e) {
                                            return (
                                              (0, n.wg)(),
                                              (0, n.iD)(
                                                'div',
                                                { key: e.code },
                                                [
                                                  (0, n._)(
                                                    'dt',
                                                    W,
                                                    (0, a.zw)(e.text),
                                                    1,
                                                  ),
                                                  (0, n._)(
                                                    'dd',
                                                    null,
                                                    (0, a.zw)(e.value),
                                                    1,
                                                  ),
                                                ],
                                              )
                                            );
                                          },
                                        ),
                                        128,
                                      )),
                                    ]),
                                  ];
                                }),
                                _: 1,
                              }),
                              (0, n.Wm)((0, o.SU)(r.x4), null, {
                                default: (0, n.w5)(function () {
                                  return [
                                    (0, n._)('dl', z, [
                                      ((0, n.wg)(!0),
                                      (0, n.iD)(
                                        n.HY,
                                        null,
                                        (0, n.Ko)(
                                          e.plan.benefits.exclusion || [],
                                          function (e) {
                                            return (
                                              (0, n.wg)(),
                                              (0, n.iD)(
                                                'div',
                                                { key: e.code },
                                                [
                                                  (0, n._)(
                                                    'dt',
                                                    E,
                                                    (0, a.zw)(e.text),
                                                    1,
                                                  ),
                                                  (0, n._)(
                                                    'dd',
                                                    null,
                                                    (0, a.zw)(e.value),
                                                    1,
                                                  ),
                                                ],
                                              )
                                            );
                                          },
                                        ),
                                        128,
                                      )),
                                    ]),
                                  ];
                                }),
                                _: 1,
                              }),
                              (0, n.Wm)((0, o.SU)(r.x4), null, {
                                default: (0, n.w5)(function () {
                                  return [
                                    (0, n._)('dl', D, [
                                      ((0, n.wg)(!0),
                                      (0, n.iD)(
                                        n.HY,
                                        null,
                                        (0, n.Ko)(
                                          e.plan.benefits.networkLink || [],
                                          function (e) {
                                            return (
                                              (0, n.wg)(),
                                              (0, n.j4)(
                                                O,
                                                {
                                                  key: e.code,
                                                  href: e.value,
                                                  target: '_blank',
                                                  title: 'Open File',
                                                  external: '',
                                                },
                                                {
                                                  default: (0, n.w5)(
                                                    function () {
                                                      return [
                                                        (0, n.Uk)(
                                                          (0, a.zw)(e.text),
                                                          1,
                                                        ),
                                                      ];
                                                    },
                                                  ),
                                                  _: 2,
                                                },
                                                1032,
                                                ['href'],
                                              )
                                            );
                                          },
                                        ),
                                        128,
                                      )),
                                    ]),
                                  ];
                                }),
                                _: 1,
                              }),
                            ];
                          }),
                          _: 1,
                        },
                      ),
                    ];
                  }),
                  _: 1,
                }),
              ])
            );
          };
        },
      };
    },
    7826: (e, t, l) => {
      'use strict';
      l.r(t), l.d(t, { default: () => i });
      var n = l(6252),
        o = l(2610),
        a = l(8433),
        r = { class: 'grid gap-5' };
      const i = {
        __name: 'CreatePlan',
        props: { uuid: String },
        emits: ['success', 'error'],
        setup: function (e, t) {
          var l = t.emit,
            i = e,
            u = (0, o.qj)({ insurancePlans: [], loading: !1 }),
            s = (0, o.qj)({
              provider_id: null,
              plan_id: null,
              premium: null,
              loading: !1,
            }),
            d = {
              isRequired: function (e) {
                return !!e || 'This field is required';
              },
              isNumber: function (e) {
                return !isNaN(e) || 'This field must be a number';
              },
            },
            c = function (e) {
              e &&
                ((s.loading = !0),
                a.Z.post('/health-plan-manual-create', {
                  quoteUID: i.uuid,
                  planId: s.plan_id,
                  actualPremium: s.premium,
                })
                  .then(function (e) {
                    200 == e.data ? l('success') : l('error');
                  })
                  .catch(function (e) {
                    l('error');
                  })
                  .finally(function () {
                    s.loading = !1;
                  }));
            };
          return (
            (0, n.YP)(
              function () {
                return null == s ? void 0 : s.provider_id;
              },
              function (e) {
                e &&
                  ((u.loading = !0),
                  a.Z.get(
                    '/insurance-provider-plans-health?insuranceProviderId='
                      .concat(e, '&quoteUuId=')
                      .concat(i.uuid),
                  )
                    .then(function (e) {
                      e.data.length > 0 && (u.insurancePlans = e.data);
                    })
                    .finally(function () {
                      (u.loading = !1), (s.plan_id = null);
                    }));
              },
            ),
            function (e, t) {
              var l = (0, n.up)('x-select'),
                o = (0, n.up)('x-input'),
                a = (0, n.up)('x-button'),
                i = (0, n.up)('x-form');
              return (
                (0, n.wg)(),
                (0, n.j4)(
                  i,
                  { onSubmit: c, 'auto-focus': !1 },
                  {
                    default: (0, n.w5)(function () {
                      var i, c, m;
                      return [
                        (0, n._)('div', r, [
                          (0, n.Wm)(
                            l,
                            {
                              modelValue: s.provider_id,
                              'onUpdate:modelValue':
                                t[0] ||
                                (t[0] = function (e) {
                                  return (s.provider_id = e);
                                }),
                              options:
                                null ===
                                  (i = e.$page.props.insuranceProviders) ||
                                void 0 === i
                                  ? void 0
                                  : i.map(function (e) {
                                      return { value: e.id, label: e.text };
                                    }),
                              label: 'Provider',
                              placeholder: 'Select Provider',
                              disabled:
                                0 ==
                                (null ===
                                  (c = e.$page.props.insuranceProviders) ||
                                void 0 === c
                                  ? void 0
                                  : c.length),
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue', 'options', 'disabled'],
                          ),
                          (0, n.Wm)(
                            l,
                            {
                              modelValue: s.plan_id,
                              'onUpdate:modelValue':
                                t[1] ||
                                (t[1] = function (e) {
                                  return (s.plan_id = e);
                                }),
                              label: 'Plan',
                              placeholder: 'Select Plan',
                              disabled: !s.provider_id,
                              class: 'w-full',
                              helper: s.provider_id
                                ? ''
                                : 'Select a provider first',
                              options:
                                null === (m = u.insurancePlans) || void 0 === m
                                  ? void 0
                                  : m.map(function (e) {
                                      return { value: e.id, label: e.text };
                                    }),
                              loading: u.loading,
                              rules: [d.isRequired],
                            },
                            null,
                            8,
                            [
                              'modelValue',
                              'disabled',
                              'helper',
                              'options',
                              'loading',
                              'rules',
                            ],
                          ),
                          (0, n.Wm)(
                            o,
                            {
                              modelValue: s.premium,
                              'onUpdate:modelValue':
                                t[2] ||
                                (t[2] = function (e) {
                                  return (s.premium = e);
                                }),
                              type: 'text',
                              label: 'Premium',
                              placeholder:
                                'Enter Premium (inclusive of VAT, Basmah and Policy fee)',
                              class: 'w-full',
                              rules: [d.isRequired, d.isNumber],
                            },
                            null,
                            8,
                            ['modelValue', 'rules'],
                          ),
                          (0, n.Wm)(
                            a,
                            {
                              type: 'submit',
                              class: 'w-full',
                              color: 'primary',
                              loading: s.loading,
                            },
                            {
                              default: (0, n.w5)(function () {
                                return [(0, n.Uk)(' Add Quote ')];
                              }),
                              _: 1,
                            },
                            8,
                            ['loading'],
                          ),
                        ]),
                      ];
                    }),
                    _: 1,
                  },
                )
              );
            }
          );
        },
      };
    },
    1314: (e, t, l) => {
      'use strict';
      l.r(t), l.d(t, { default: () => z });
      var n = l(6252),
        o = l(3577),
        a = l(2610),
        r = l(9118),
        i = { class: 'p-4' },
        u = (0, n._)(
          'span',
          { class: 'block text-gray-700 text-xs' },
          ' Drop file here ',
          -1,
        ),
        s = (0, n._)(
          'span',
          { class: 'block mb-2 mt-1 text-gray-700 text-xs' },
          ' or ',
          -1,
        );
      const d = {
        __name: 'Dropzone',
        props: {
          modelValue: Array,
          accept: { type: [Array, String], default: null },
          loading: { type: Boolean, default: !1 },
          maxSize: { type: Number, default: 10 },
          maxFiles: { type: Number, default: 1 },
        },
        emits: ['update:modelValue', 'change'],
        setup: function (e, t) {
          var l = t.emit,
            d = e,
            c = (0, r.u)({
              onDrop: function (e) {
                var t = e.map(function (e) {
                  return { file: e };
                });
                l('update:modelValue', t), l('change', t);
              },
              multiple: !1,
              accept: d.accept,
              noClick: !0,
              maxFiles: d.maxFiles,
              maxSize: 1024 * d.maxSize * 1024,
            }),
            m = c.getRootProps,
            p = c.getInputProps,
            f = c.open,
            _ = c.isDragActive;
          return function (t, l) {
            var r = (0, n.up)('x-button');
            return (
              (0, n.wg)(),
              (0, n.iD)(
                'div',
                (0, n.dG)((0, a.SU)(m)(), {
                  class: [
                    'relative bg-primary-50 rounded-md text-center flex flex-col gap-4 items-center border border-primary-300 ease-linear transition-all duration-150',
                    [(0, a.SU)(_) ? 'border-primary-600 bg-primary-100' : ''],
                  ],
                }),
                [
                  (0, n._)('div', i, [
                    (0, n._)(
                      'input',
                      (0, o.vs)((0, n.F4)((0, a.SU)(p)())),
                      null,
                      16,
                    ),
                    u,
                    s,
                    (0, n.Wm)(
                      r,
                      { onClick: (0, a.SU)(f), size: 'xs', loading: e.loading },
                      {
                        default: (0, n.w5)(function () {
                          return [(0, n.Uk)(' Click to browse ')];
                        }),
                        _: 1,
                      },
                      8,
                      ['onClick', 'loading'],
                    ),
                  ]),
                ],
                16,
              )
            );
          };
        },
      };
      var c = l(9285),
        m = l(9145);
      function p(e, t) {
        var l = Object.keys(e);
        if (Object.getOwnPropertySymbols) {
          var n = Object.getOwnPropertySymbols(e);
          t &&
            (n = n.filter(function (t) {
              return Object.getOwnPropertyDescriptor(e, t).enumerable;
            })),
            l.push.apply(l, n);
        }
        return l;
      }
      function f(e) {
        for (var t = 1; t < arguments.length; t++) {
          var l = null != arguments[t] ? arguments[t] : {};
          t % 2
            ? p(Object(l), !0).forEach(function (t) {
                _(e, t, l[t]);
              })
            : Object.getOwnPropertyDescriptors
            ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(l))
            : p(Object(l)).forEach(function (t) {
                Object.defineProperty(
                  e,
                  t,
                  Object.getOwnPropertyDescriptor(l, t),
                );
              });
        }
        return e;
      }
      function _(e, t, l) {
        return (
          t in e
            ? Object.defineProperty(e, t, {
                value: l,
                enumerable: !0,
                configurable: !0,
                writable: !0,
              })
            : (e[t] = l),
          e
        );
      }
      var v = { class: 'flex flex-col gap-1' },
        g = { class: 'text-sm font-semibold' },
        w = { class: 'text-xs' },
        h = { class: 'text-xs' },
        b = { class: 'text-xs' },
        y = { class: 'pb-4' },
        x = ['href'],
        U = { class: 'flex flex-col gap-1' },
        S = { class: 'text-sm font-semibold' },
        V = { class: 'text-xs' },
        k = { class: 'text-xs' },
        q = { class: 'text-xs' },
        C = { class: 'pb-4' },
        W = ['href'];
      const z = {
        __name: 'DocumentUploader',
        props: { members: Array, docTypes: Object, docs: Array, cdn: String },
        setup: function (e) {
          var t = (0, a.iH)('quote-documents'),
            l = (0, a.iH)(!1),
            r = (0, m.zn)('toast'),
            i = (0, c.cI)({
              quote_id: (0, c.qt)().props.quote.id || null,
              quote_uuid: (0, c.qt)().props.quote.code || null,
              quote_type_id: null,
              document_type_code: null,
              folder_path: null,
              member_detail_id: null,
              file: null,
            }),
            u = function (e, t, n) {
              0 != n.length &&
                ((l.value = !0),
                i
                  .transform(function (l) {
                    return f(
                      f({}, l),
                      {},
                      {
                        quote_type_id: e.quote_type_id,
                        document_type_code: e.code,
                        folder_path: e.folder_path,
                        member_detail_id: t || null,
                        file: n[0].file,
                      },
                    );
                  })
                  .post('/quotes/health/documents/store', {
                    preserveScroll: !0,
                    preserveState: !0,
                    only: ['quoteDocuments'],
                    onFinish: function () {
                      (l.value = !1),
                        r.success({ title: 'File Uploaded', position: 'top' });
                    },
                  }));
            };
          return function (l, r) {
            var s = (0, n.up)('x-tab'),
              c = (0, n.up)('x-tab-group');
            return (
              (0, n.wg)(),
              (0, n.iD)('div', null, [
                (0, n._)('div', null, [
                  (0, n.Wm)(
                    c,
                    {
                      modelValue: t.value,
                      'onUpdate:modelValue':
                        r[0] ||
                        (r[0] = function (e) {
                          return (t.value = e);
                        }),
                      class: 'pb-10',
                      variant: 'block',
                    },
                    {
                      default: (0, n.w5)(function () {
                        return [
                          (0, n.Wm)(
                            s,
                            { value: 'quote-documents', label: 'Documents' },
                            {
                              default: (0, n.w5)(function () {
                                return [
                                  ((0, n.wg)(!0),
                                  (0, n.iD)(
                                    n.HY,
                                    null,
                                    (0, n.Ko)(e.docTypes.QUOTE, function (t) {
                                      return (
                                        (0, n.wg)(),
                                        (0, n.iD)(
                                          'div',
                                          {
                                            key: t.id,
                                            class:
                                              'grid md:grid-cols-2 gap-2 my-4 border-b',
                                          },
                                          [
                                            (0, n._)('div', v, [
                                              (0, n._)(
                                                'h5',
                                                g,
                                                (0, o.zw)(t.text),
                                                1,
                                              ),
                                              (0, n._)(
                                                'p',
                                                w,
                                                'Max files: ' +
                                                  (0, o.zw)(t.max_files),
                                                1,
                                              ),
                                              (0, n._)(
                                                'p',
                                                h,
                                                'Supported: ' +
                                                  (0, o.zw)(t.accepted_files),
                                                1,
                                              ),
                                              (0, n._)(
                                                'p',
                                                b,
                                                'Max file size: ' +
                                                  (0, o.zw)(t.max_size) +
                                                  ' MB',
                                                1,
                                              ),
                                            ]),
                                            (0, n._)('div', y, [
                                              (0, n.Wm)(
                                                d,
                                                {
                                                  id: t.id,
                                                  accept: t.accepted_files,
                                                  'max-files': t.max_files,
                                                  'max-size': t.max_size,
                                                  loading: (0, a.SU)(i)
                                                    .processing,
                                                  onChange: function (e) {
                                                    return u(t, null, e);
                                                  },
                                                },
                                                null,
                                                8,
                                                [
                                                  'id',
                                                  'accept',
                                                  'max-files',
                                                  'max-size',
                                                  'loading',
                                                  'onChange',
                                                ],
                                              ),
                                              ((0, n.wg)(!0),
                                              (0, n.iD)(
                                                n.HY,
                                                null,
                                                (0, n.Ko)(
                                                  e.docs.filter(function (e) {
                                                    return (
                                                      e.document_type_code ==
                                                      t.code
                                                    );
                                                  }),
                                                  function (t) {
                                                    return (
                                                      (0, n.wg)(),
                                                      (0, n.iD)(
                                                        'a',
                                                        {
                                                          key: t.id,
                                                          href:
                                                            e.cdn + t.doc_url,
                                                          target: '_blank',
                                                          class:
                                                            'block px-2 py-1 border rounded mt-1 text-xs hover:text-primary-600 truncate',
                                                        },
                                                        (0, o.zw)(
                                                          t.original_name ||
                                                            t.doc_name,
                                                        ),
                                                        9,
                                                        x,
                                                      )
                                                    );
                                                  },
                                                ),
                                                128,
                                              )),
                                            ]),
                                          ],
                                        )
                                      );
                                    }),
                                    128,
                                  )),
                                ];
                              }),
                              _: 1,
                            },
                          ),
                          ((0, n.wg)(!0),
                          (0, n.iD)(
                            n.HY,
                            null,
                            (0, n.Ko)(e.members, function (t) {
                              return (
                                (0, n.wg)(),
                                (0, n.j4)(
                                  s,
                                  {
                                    key: t.id,
                                    value: 'member-'.concat(t.id),
                                    label: t.name,
                                  },
                                  {
                                    default: (0, n.w5)(function () {
                                      return [
                                        ((0, n.wg)(!0),
                                        (0, n.iD)(
                                          n.HY,
                                          null,
                                          (0, n.Ko)(
                                            e.docTypes.MEMBER,
                                            function (l) {
                                              return (
                                                (0, n.wg)(),
                                                (0, n.iD)(
                                                  'div',
                                                  {
                                                    key: l.id,
                                                    class:
                                                      'grid md:grid-cols-2 gap-2 my-4 border-b',
                                                  },
                                                  [
                                                    (0, n._)('div', U, [
                                                      (0, n._)(
                                                        'h5',
                                                        S,
                                                        (0, o.zw)(l.text),
                                                        1,
                                                      ),
                                                      (0, n._)(
                                                        'p',
                                                        V,
                                                        'Max files: ' +
                                                          (0, o.zw)(
                                                            l.max_files,
                                                          ),
                                                        1,
                                                      ),
                                                      (0, n._)(
                                                        'p',
                                                        k,
                                                        'Supported: ' +
                                                          (0, o.zw)(
                                                            l.accepted_files,
                                                          ),
                                                        1,
                                                      ),
                                                      (0, n._)(
                                                        'p',
                                                        q,
                                                        'Max file size: ' +
                                                          (0, o.zw)(
                                                            l.max_size,
                                                          ) +
                                                          ' MB',
                                                        1,
                                                      ),
                                                    ]),
                                                    (0, n._)('div', C, [
                                                      (0, n.Wm)(
                                                        d,
                                                        {
                                                          id: l.id,
                                                          accept:
                                                            l.accepted_files,
                                                          'max-files':
                                                            l.max_files,
                                                          'max-size':
                                                            l.max_size,
                                                          loading: (0, a.SU)(i)
                                                            .processing,
                                                          onChange: function (
                                                            e,
                                                          ) {
                                                            return u(
                                                              l,
                                                              t.id,
                                                              e,
                                                            );
                                                          },
                                                        },
                                                        null,
                                                        8,
                                                        [
                                                          'id',
                                                          'accept',
                                                          'max-files',
                                                          'max-size',
                                                          'loading',
                                                          'onChange',
                                                        ],
                                                      ),
                                                      ((0, n.wg)(!0),
                                                      (0, n.iD)(
                                                        n.HY,
                                                        null,
                                                        (0, n.Ko)(
                                                          e.docs.filter(
                                                            function (e) {
                                                              return (
                                                                e.document_type_code ==
                                                                  l.code &&
                                                                e.member_detail_id ==
                                                                  t.id
                                                              );
                                                            },
                                                          ),
                                                          function (t) {
                                                            return (
                                                              (0, n.wg)(),
                                                              (0, n.iD)(
                                                                'a',
                                                                {
                                                                  key: t.id,
                                                                  href:
                                                                    e.cdn +
                                                                    t.doc_url,
                                                                  target:
                                                                    '_blank',
                                                                  class:
                                                                    'block px-2 py-1 border rounded mt-1 text-xs hover:text-primary-600 truncate',
                                                                },
                                                                (0, o.zw)(
                                                                  t.original_name ||
                                                                    t.doc_name,
                                                                ),
                                                                9,
                                                                W,
                                                              )
                                                            );
                                                          },
                                                        ),
                                                        128,
                                                      )),
                                                    ]),
                                                  ],
                                                )
                                              );
                                            },
                                          ),
                                          128,
                                        )),
                                      ];
                                    }),
                                    _: 2,
                                  },
                                  1032,
                                  ['value', 'label'],
                                )
                              );
                            }),
                            128,
                          )),
                        ];
                      }),
                      _: 1,
                    },
                    8,
                    ['modelValue'],
                  ),
                ]),
              ])
            );
          };
        },
      };
    },
    4377: (e, t, l) => {
      'use strict';
      l.r(t), l.d(t, { default: () => Ol });
      var n = l(6252),
        o = l(2610),
        a = l(9963),
        r = l(3577),
        i = l(9285),
        u = l(3299),
        s = l(6309),
        d = l(1314),
        c = l(8876),
        m = l(7826),
        p = l(9145),
        f = l(8433),
        _ = l(2726);
      function v(e) {
        return (
          (v =
            'function' == typeof Symbol && 'symbol' == typeof Symbol.iterator
              ? function (e) {
                  return typeof e;
                }
              : function (e) {
                  return e &&
                    'function' == typeof Symbol &&
                    e.constructor === Symbol &&
                    e !== Symbol.prototype
                    ? 'symbol'
                    : typeof e;
                }),
          v(e)
        );
      }
      function g() {
        /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ g =
          function () {
            return e;
          };
        var e = {},
          t = Object.prototype,
          l = t.hasOwnProperty,
          n = 'function' == typeof Symbol ? Symbol : {},
          o = n.iterator || '@@iterator',
          a = n.asyncIterator || '@@asyncIterator',
          r = n.toStringTag || '@@toStringTag';
        function i(e, t, l) {
          return (
            Object.defineProperty(e, t, {
              value: l,
              enumerable: !0,
              configurable: !0,
              writable: !0,
            }),
            e[t]
          );
        }
        try {
          i({}, '');
        } catch (e) {
          i = function (e, t, l) {
            return (e[t] = l);
          };
        }
        function u(e, t, l, n) {
          var o = t && t.prototype instanceof c ? t : c,
            a = Object.create(o.prototype),
            r = new V(n || []);
          return (
            (a._invoke = (function (e, t, l) {
              var n = 'suspendedStart';
              return function (o, a) {
                if ('executing' === n)
                  throw new Error('Generator is already running');
                if ('completed' === n) {
                  if ('throw' === o) throw a;
                  return q();
                }
                for (l.method = o, l.arg = a; ; ) {
                  var r = l.delegate;
                  if (r) {
                    var i = x(r, l);
                    if (i) {
                      if (i === d) continue;
                      return i;
                    }
                  }
                  if ('next' === l.method) l.sent = l._sent = l.arg;
                  else if ('throw' === l.method) {
                    if ('suspendedStart' === n)
                      throw ((n = 'completed'), l.arg);
                    l.dispatchException(l.arg);
                  } else 'return' === l.method && l.abrupt('return', l.arg);
                  n = 'executing';
                  var u = s(e, t, l);
                  if ('normal' === u.type) {
                    if (
                      ((n = l.done ? 'completed' : 'suspendedYield'),
                      u.arg === d)
                    )
                      continue;
                    return { value: u.arg, done: l.done };
                  }
                  'throw' === u.type &&
                    ((n = 'completed'), (l.method = 'throw'), (l.arg = u.arg));
                }
              };
            })(e, l, r)),
            a
          );
        }
        function s(e, t, l) {
          try {
            return { type: 'normal', arg: e.call(t, l) };
          } catch (e) {
            return { type: 'throw', arg: e };
          }
        }
        e.wrap = u;
        var d = {};
        function c() {}
        function m() {}
        function p() {}
        var f = {};
        i(f, o, function () {
          return this;
        });
        var _ = Object.getPrototypeOf,
          w = _ && _(_(k([])));
        w && w !== t && l.call(w, o) && (f = w);
        var h = (p.prototype = c.prototype = Object.create(f));
        function b(e) {
          ['next', 'throw', 'return'].forEach(function (t) {
            i(e, t, function (e) {
              return this._invoke(t, e);
            });
          });
        }
        function y(e, t) {
          function n(o, a, r, i) {
            var u = s(e[o], e, a);
            if ('throw' !== u.type) {
              var d = u.arg,
                c = d.value;
              return c && 'object' == v(c) && l.call(c, '__await')
                ? t.resolve(c.__await).then(
                    function (e) {
                      n('next', e, r, i);
                    },
                    function (e) {
                      n('throw', e, r, i);
                    },
                  )
                : t.resolve(c).then(
                    function (e) {
                      (d.value = e), r(d);
                    },
                    function (e) {
                      return n('throw', e, r, i);
                    },
                  );
            }
            i(u.arg);
          }
          var o;
          this._invoke = function (e, l) {
            function a() {
              return new t(function (t, o) {
                n(e, l, t, o);
              });
            }
            return (o = o ? o.then(a, a) : a());
          };
        }
        function x(e, t) {
          var l = e.iterator[t.method];
          if (void 0 === l) {
            if (((t.delegate = null), 'throw' === t.method)) {
              if (
                e.iterator.return &&
                ((t.method = 'return'),
                (t.arg = void 0),
                x(e, t),
                'throw' === t.method)
              )
                return d;
              (t.method = 'throw'),
                (t.arg = new TypeError(
                  "The iterator does not provide a 'throw' method",
                ));
            }
            return d;
          }
          var n = s(l, e.iterator, t.arg);
          if ('throw' === n.type)
            return (
              (t.method = 'throw'), (t.arg = n.arg), (t.delegate = null), d
            );
          var o = n.arg;
          return o
            ? o.done
              ? ((t[e.resultName] = o.value),
                (t.next = e.nextLoc),
                'return' !== t.method &&
                  ((t.method = 'next'), (t.arg = void 0)),
                (t.delegate = null),
                d)
              : o
            : ((t.method = 'throw'),
              (t.arg = new TypeError('iterator result is not an object')),
              (t.delegate = null),
              d);
        }
        function U(e) {
          var t = { tryLoc: e[0] };
          1 in e && (t.catchLoc = e[1]),
            2 in e && ((t.finallyLoc = e[2]), (t.afterLoc = e[3])),
            this.tryEntries.push(t);
        }
        function S(e) {
          var t = e.completion || {};
          (t.type = 'normal'), delete t.arg, (e.completion = t);
        }
        function V(e) {
          (this.tryEntries = [{ tryLoc: 'root' }]),
            e.forEach(U, this),
            this.reset(!0);
        }
        function k(e) {
          if (e) {
            var t = e[o];
            if (t) return t.call(e);
            if ('function' == typeof e.next) return e;
            if (!isNaN(e.length)) {
              var n = -1,
                a = function t() {
                  for (; ++n < e.length; )
                    if (l.call(e, n)) return (t.value = e[n]), (t.done = !1), t;
                  return (t.value = void 0), (t.done = !0), t;
                };
              return (a.next = a);
            }
          }
          return { next: q };
        }
        function q() {
          return { value: void 0, done: !0 };
        }
        return (
          (m.prototype = p),
          i(h, 'constructor', p),
          i(p, 'constructor', m),
          (m.displayName = i(p, r, 'GeneratorFunction')),
          (e.isGeneratorFunction = function (e) {
            var t = 'function' == typeof e && e.constructor;
            return (
              !!t &&
              (t === m || 'GeneratorFunction' === (t.displayName || t.name))
            );
          }),
          (e.mark = function (e) {
            return (
              Object.setPrototypeOf
                ? Object.setPrototypeOf(e, p)
                : ((e.__proto__ = p), i(e, r, 'GeneratorFunction')),
              (e.prototype = Object.create(h)),
              e
            );
          }),
          (e.awrap = function (e) {
            return { __await: e };
          }),
          b(y.prototype),
          i(y.prototype, a, function () {
            return this;
          }),
          (e.AsyncIterator = y),
          (e.async = function (t, l, n, o, a) {
            void 0 === a && (a = Promise);
            var r = new y(u(t, l, n, o), a);
            return e.isGeneratorFunction(l)
              ? r
              : r.next().then(function (e) {
                  return e.done ? e.value : r.next();
                });
          }),
          b(h),
          i(h, r, 'Generator'),
          i(h, o, function () {
            return this;
          }),
          i(h, 'toString', function () {
            return '[object Generator]';
          }),
          (e.keys = function (e) {
            var t = [];
            for (var l in e) t.push(l);
            return (
              t.reverse(),
              function l() {
                for (; t.length; ) {
                  var n = t.pop();
                  if (n in e) return (l.value = n), (l.done = !1), l;
                }
                return (l.done = !0), l;
              }
            );
          }),
          (e.values = k),
          (V.prototype = {
            constructor: V,
            reset: function (e) {
              if (
                ((this.prev = 0),
                (this.next = 0),
                (this.sent = this._sent = void 0),
                (this.done = !1),
                (this.delegate = null),
                (this.method = 'next'),
                (this.arg = void 0),
                this.tryEntries.forEach(S),
                !e)
              )
                for (var t in this)
                  't' === t.charAt(0) &&
                    l.call(this, t) &&
                    !isNaN(+t.slice(1)) &&
                    (this[t] = void 0);
            },
            stop: function () {
              this.done = !0;
              var e = this.tryEntries[0].completion;
              if ('throw' === e.type) throw e.arg;
              return this.rval;
            },
            dispatchException: function (e) {
              if (this.done) throw e;
              var t = this;
              function n(l, n) {
                return (
                  (r.type = 'throw'),
                  (r.arg = e),
                  (t.next = l),
                  n && ((t.method = 'next'), (t.arg = void 0)),
                  !!n
                );
              }
              for (var o = this.tryEntries.length - 1; o >= 0; --o) {
                var a = this.tryEntries[o],
                  r = a.completion;
                if ('root' === a.tryLoc) return n('end');
                if (a.tryLoc <= this.prev) {
                  var i = l.call(a, 'catchLoc'),
                    u = l.call(a, 'finallyLoc');
                  if (i && u) {
                    if (this.prev < a.catchLoc) return n(a.catchLoc, !0);
                    if (this.prev < a.finallyLoc) return n(a.finallyLoc);
                  } else if (i) {
                    if (this.prev < a.catchLoc) return n(a.catchLoc, !0);
                  } else {
                    if (!u)
                      throw new Error('try statement without catch or finally');
                    if (this.prev < a.finallyLoc) return n(a.finallyLoc);
                  }
                }
              }
            },
            abrupt: function (e, t) {
              for (var n = this.tryEntries.length - 1; n >= 0; --n) {
                var o = this.tryEntries[n];
                if (
                  o.tryLoc <= this.prev &&
                  l.call(o, 'finallyLoc') &&
                  this.prev < o.finallyLoc
                ) {
                  var a = o;
                  break;
                }
              }
              a &&
                ('break' === e || 'continue' === e) &&
                a.tryLoc <= t &&
                t <= a.finallyLoc &&
                (a = null);
              var r = a ? a.completion : {};
              return (
                (r.type = e),
                (r.arg = t),
                a
                  ? ((this.method = 'next'), (this.next = a.finallyLoc), d)
                  : this.complete(r)
              );
            },
            complete: function (e, t) {
              if ('throw' === e.type) throw e.arg;
              return (
                'break' === e.type || 'continue' === e.type
                  ? (this.next = e.arg)
                  : 'return' === e.type
                  ? ((this.rval = this.arg = e.arg),
                    (this.method = 'return'),
                    (this.next = 'end'))
                  : 'normal' === e.type && t && (this.next = t),
                d
              );
            },
            finish: function (e) {
              for (var t = this.tryEntries.length - 1; t >= 0; --t) {
                var l = this.tryEntries[t];
                if (l.finallyLoc === e)
                  return this.complete(l.completion, l.afterLoc), S(l), d;
              }
            },
            catch: function (e) {
              for (var t = this.tryEntries.length - 1; t >= 0; --t) {
                var l = this.tryEntries[t];
                if (l.tryLoc === e) {
                  var n = l.completion;
                  if ('throw' === n.type) {
                    var o = n.arg;
                    S(l);
                  }
                  return o;
                }
              }
              throw new Error('illegal catch attempt');
            },
            delegateYield: function (e, t, l) {
              return (
                (this.delegate = { iterator: k(e), resultName: t, nextLoc: l }),
                'next' === this.method && (this.arg = void 0),
                d
              );
            },
          }),
          e
        );
      }
      function w(e, t, l, n, o, a, r) {
        try {
          var i = e[a](r),
            u = i.value;
        } catch (e) {
          return void l(e);
        }
        i.done ? t(u) : Promise.resolve(u).then(n, o);
      }
      function h(e, t) {
        var l = Object.keys(e);
        if (Object.getOwnPropertySymbols) {
          var n = Object.getOwnPropertySymbols(e);
          t &&
            (n = n.filter(function (t) {
              return Object.getOwnPropertyDescriptor(e, t).enumerable;
            })),
            l.push.apply(l, n);
        }
        return l;
      }
      function b(e) {
        for (var t = 1; t < arguments.length; t++) {
          var l = null != arguments[t] ? arguments[t] : {};
          t % 2
            ? h(Object(l), !0).forEach(function (t) {
                y(e, t, l[t]);
              })
            : Object.getOwnPropertyDescriptors
            ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(l))
            : h(Object(l)).forEach(function (t) {
                Object.defineProperty(
                  e,
                  t,
                  Object.getOwnPropertyDescriptor(l, t),
                );
              });
        }
        return e;
      }
      function y(e, t, l) {
        return (
          t in e
            ? Object.defineProperty(e, t, {
                value: l,
                enumerable: !0,
                configurable: !0,
                writable: !0,
              })
            : (e[t] = l),
          e
        );
      }
      var x = { class: 'flex justify-between items-center flex-wrap gap-2' },
        U = (0, n._)(
          'h2',
          { class: 'text-xl font-semibold' },
          'Health Detail',
          -1,
        ),
        S = { class: 'flex gap-2' },
        V = { class: 'grid gap-4' },
        k = { class: 'p-4 rounded shadow mb-6 bg-primary-50/50' },
        q = { class: 'flex flex-wrap md:flex-nowrap gap-6 w-full' },
        C = { class: 'w-full md:w-1/2 flex gap-2 items-end' },
        W = { class: 'w-full md:w-1/2 flex gap-2 items-end' },
        z = { class: 'p-4 rounded shadow mb-6 bg-white' },
        E = { class: 'text-sm' },
        D = { class: 'grid md:grid-cols-2 gap-x-6 gap-y-4' },
        A = { class: 'grid sm:grid-cols-2' },
        M = (0, n._)('dt', { class: 'font-medium' }, 'CDB ID', -1),
        O = { class: 'grid sm:grid-cols-2' },
        P = (0, n._)('dt', { class: 'font-medium' }, 'CREATED DATE', -1),
        L = { class: 'grid sm:grid-cols-2' },
        T = (0, n._)('dt', { class: 'font-medium' }, 'SUBTEAM', -1),
        R = { class: 'grid sm:grid-cols-2' },
        N = (0, n._)('dt', { class: 'font-medium' }, 'ADVISOR', -1),
        I = { class: 'grid sm:grid-cols-2' },
        j = (0, n._)('dt', { class: 'font-medium' }, 'SOURCE', -1),
        H = { class: 'grid sm:grid-cols-2' },
        B = (0, n._)('dt', { class: 'font-medium' }, 'LAST MODIFIED DATE', -1),
        F = { class: 'grid sm:grid-cols-2' },
        Y = (0, n._)('dt', { class: 'font-medium' }, 'PARENT CDB ID', -1),
        Q = { class: 'grid sm:grid-cols-2' },
        K = (0, n._)('dt', { class: 'font-medium' }, 'IS ECOMMERCE', -1),
        Z = { class: 'grid sm:grid-cols-2' },
        G = (0, n._)('dt', { class: 'font-medium' }, 'IS EBP RENEWAL', -1),
        $ = { class: 'grid sm:grid-cols-2' },
        J = (0, n._)('dt', { class: 'font-medium' }, 'RENEWAL BATCH', -1),
        X = { class: 'grid sm:grid-cols-2' },
        ee = (0, n._)('dt', { class: 'font-medium' }, 'LOST REASON', -1),
        te = { class: 'grid sm:grid-cols-2' },
        le = (0, n._)('dt', { class: 'font-medium' }, 'DEVICE', -1),
        ne = { class: 'mt-6' },
        oe = (0, n._)(
          'h3',
          { class: 'font-semibold text-primary-800' },
          'Customer Profile',
          -1,
        ),
        ae = { class: 'text-sm' },
        re = { class: 'grid md:grid-cols-2 gap-x-6 gap-y-4' },
        ie = { class: 'grid sm:grid-cols-2' },
        ue = (0, n._)('dt', { class: 'font-medium' }, 'FIRST NAME', -1),
        se = { class: 'grid sm:grid-cols-2' },
        de = (0, n._)('dt', { class: 'font-medium' }, 'LAST NAME', -1),
        ce = { class: 'grid sm:grid-cols-2' },
        me = (0, n._)('dt', { class: 'font-medium' }, 'MOBILE NUMBER', -1),
        pe = { class: 'grid sm:grid-cols-2' },
        fe = (0, n._)('dt', { class: 'font-medium' }, 'EMAIL', -1),
        _e = { class: 'grid sm:grid-cols-2' },
        ve = (0, n._)('dt', { class: 'font-medium' }, 'GENDER', -1),
        ge = { class: 'grid sm:grid-cols-2' },
        we = (0, n._)('dt', { class: 'font-medium' }, 'MARITAL STATUS', -1),
        he = { class: 'grid sm:grid-cols-2' },
        be = (0, n._)('dt', { class: 'font-medium' }, 'NATIONALITY', -1),
        ye = { class: 'grid sm:grid-cols-2' },
        xe = (0, n._)('dt', { class: 'font-medium' }, 'DATE OF BIRTH', -1),
        Ue = { class: 'grid sm:grid-cols-2' },
        Se = (0, n._)('dt', { class: 'font-medium' }, 'EMIRATE OF VISA', -1),
        Ve = { class: 'grid sm:grid-cols-2' },
        ke = (0, n._)('dt', { class: 'font-medium' }, 'MEMBER CATEGORY', -1),
        qe = { class: 'grid sm:grid-cols-2' },
        Ce = (0, n._)('dt', { class: 'font-medium' }, 'SALARY BAND', -1),
        We = { class: 'mt-6' },
        ze = (0, n._)(
          'h3',
          { class: 'font-semibold text-primary-800' },
          'Quote Details',
          -1,
        ),
        Ee = { class: 'text-sm' },
        De = { class: 'grid md:grid-cols-2 gap-x-6 gap-y-4' },
        Ae = { class: 'grid sm:grid-cols-2' },
        Me = (0, n._)(
          'dt',
          { class: 'font-medium' },
          'WHO ARE YOU LOOKING TO COVER?',
          -1,
        ),
        Oe = { class: 'grid sm:grid-cols-2' },
        Pe = (0, n._)(
          'dt',
          { class: 'font-medium' },
          'CURRENTLY INSURED WITH',
          -1,
        ),
        Le = { class: 'grid sm:grid-cols-2' },
        Te = (0, n._)('dt', { class: 'font-medium' }, 'TYPE OF PLAN', -1),
        Re = { class: 'grid sm:grid-cols-2' },
        Ne = (0, n._)('dt', { class: 'font-medium' }, 'NEXT FOLLOWUP DATE', -1),
        Ie = { class: 'grid sm:grid-cols-2' },
        je = (0, n._)('dt', { class: 'font-medium' }, 'DETAILS', -1),
        He = { class: 'mt-6' },
        Be = (0, n._)(
          'h3',
          { class: 'font-semibold text-primary-800' },
          " Last Year's Policy Details ",
          -1,
        ),
        Fe = { class: 'text-sm' },
        Ye = { class: 'grid md:grid-cols-2 gap-x-6 gap-y-4' },
        Qe = { class: 'grid sm:grid-cols-2' },
        Ke = (0, n._)(
          'dt',
          { class: 'font-medium' },
          'PREVIOUS POLICY NUMBER',
          -1,
        ),
        Ze = { class: 'grid sm:grid-cols-2' },
        Ge = (0, n._)(
          'dt',
          { class: 'font-medium' },
          'PREVIOUS POLICY PREMIUM',
          -1,
        ),
        $e = { class: 'grid sm:grid-cols-2' },
        Je = (0, n._)(
          'dt',
          { class: 'font-medium' },
          'PREVIOUS POLICY EXPIRY DATE',
          -1,
        ),
        Xe = { class: 'mt-6' },
        et = (0, n._)(
          'h3',
          { class: 'font-semibold text-primary-800' },
          'Policy Details',
          -1,
        ),
        tt = { class: 'text-sm' },
        lt = { class: 'grid md:grid-cols-2 gap-x-6 gap-y-4' },
        nt = { class: 'grid sm:grid-cols-2' },
        ot = (0, n._)('dt', { class: 'font-medium' }, 'POLICY NUMBER', -1),
        at = { class: 'grid sm:grid-cols-2' },
        rt = (0, n._)('dt', { class: 'font-medium' }, 'POLICY START DATE', -1),
        it = { class: 'grid sm:grid-cols-2' },
        ut = (0, n._)('dt', { class: 'font-medium' }, 'POLICY END DATE', -1),
        st = { class: 'grid sm:grid-cols-2' },
        dt = (0, n._)('dt', { class: 'font-medium' }, 'PREMIUM', -1),
        ct = { class: 'grid sm:grid-cols-2' },
        mt = (0, n._)('dt', { class: 'font-medium' }, 'TRANSAPP CODE', -1),
        pt = { class: 'p-4 rounded shadow mb-6 bg-white' },
        ft = { class: 'flex justify-between items-center mb-4' },
        _t = { class: 'font-semibold text-primary-800 text-lg' },
        vt = { class: 'flex gap-2' },
        gt = { class: 'grid md:grid-cols-2 gap-4' },
        wt = ['value'],
        ht = { class: 'text-right space-x-4 mt-8' },
        bt = (0, n._)('p', null, 'Are you sure you want to delete this?', -1),
        yt = { class: 'text-right space-x-4' },
        xt = { class: 'p-4 rounded shadow mb-6 bg-primary-50/25' },
        Ut = (0, n._)(
          'h3',
          { class: 'font-semibold text-primary-800 text-lg' },
          'Lead Status',
          -1,
        ),
        St = { class: 'flex flex-wrap md:flex-nowrap gap-6 w-full' },
        Vt = { class: 'w-full md:w-2/3' },
        kt = { class: 'w-full md:w-1/3' },
        qt = { class: 'flex flex-col gap-4' },
        Ct = { class: 'flex justify-end' },
        Wt = { class: 'p-4 rounded shadow mb-6 bg-white' },
        zt = (0, n._)(
          'h3',
          { class: 'font-semibold text-primary-800 text-lg' },
          'E-COM Details',
          -1,
        ),
        Et = { class: 'text-sm' },
        Dt = { class: 'grid md:grid-cols-2 gap-x-6 gap-y-4' },
        At = { class: 'grid sm:grid-cols-2' },
        Mt = (0, n._)('dt', { class: 'font-medium' }, 'PLAN NAME', -1),
        Ot = { class: 'grid sm:grid-cols-2' },
        Pt = (0, n._)('dt', { class: 'font-medium' }, 'PROVIDER NAME', -1),
        Lt = { class: 'grid sm:grid-cols-2' },
        Tt = (0, n._)('dt', { class: 'font-medium' }, 'PAYMENT STATUS', -1),
        Rt = { class: 'grid sm:grid-cols-2' },
        Nt = (0, n._)('dt', { class: 'font-medium' }, 'PAID AT', -1),
        It = { class: 'grid sm:grid-cols-2' },
        jt = (0, n._)('dt', { class: 'font-medium' }, 'NETWORK', -1),
        Ht = { key: 0, class: 'p-4 rounded shadow mb-6 bg-white' },
        Bt = (0, n._)(
          'h3',
          { class: 'font-semibold text-primary-800 text-lg' },
          'Policy Details',
          -1,
        ),
        Ft = { class: 'flex gap-6 w-full' },
        Yt = { class: 'w-full md:w-1/2' },
        Qt = { class: 'w-full md:w-1/2' },
        Kt = { class: 'flex gap-6 w-full' },
        Zt = { class: 'w-full md:w-1/2' },
        Gt = { class: 'w-full md:w-1/2' },
        $t = { class: 'flex gap-6 w-full' },
        Jt = { class: 'w-full md:w-1/2' },
        Xt = (0, n._)('div', { class: 'w-full md:w-1/2' }, null, -1),
        el = { key: 0, class: 'text-right space-x-4 mt-12' },
        tl = { class: 'p-4 rounded shadow mb-6 bg-white' },
        ll = {
          class: 'flex flex-wrap gap-4 justify-between items-center mb-4',
        },
        nl = { class: 'font-semibold text-primary-800 text-lg' },
        ol = { class: 'flex flex-wrap gap-3' },
        al = { class: 'flex gap-2 pr-2' },
        rl = { class: 'p-4 rounded shadow mb-6 bg-white' },
        il = { class: 'flex justify-between items-center mb-4' },
        ul = { class: 'font-semibold text-primary-800 text-lg' },
        sl = ['href'],
        dl = (0, n._)(
          'p',
          null,
          'Are you sure you want to delete this document?',
          -1,
        ),
        cl = { class: 'text-right space-x-4' },
        ml = { class: 'p-4 rounded shadow mb-6 bg-white' },
        pl = { class: 'flex justify-between items-center mb-4' },
        fl = { class: 'font-semibold text-primary-800 text-lg' },
        _l = { class: 'space-x-4' },
        vl = { class: 'grid gap-4' },
        gl = { class: 'text-right space-x-4 mt-12' },
        wl = (0, n._)(
          'p',
          null,
          'Are you sure you want to delete this activity?',
          -1,
        ),
        hl = { class: 'text-right space-x-4' },
        bl = { class: 'p-4 rounded shadow mb-6 bg-white' },
        yl = {
          class: 'flex flex-wrap gap-3 justify-between items-center mb-4',
        },
        xl = { class: 'font-semibold text-primary-800 text-lg' },
        Ul = { key: 0 },
        Sl = { key: 1 },
        Vl = { class: 'space-x-4' },
        kl = { class: 'grid gap-4' },
        ql = { class: 'text-right space-x-4 mt-12' },
        Cl = (0, n._)('p', null, 'Are you sure you want to delete this?', -1),
        Wl = { class: 'text-right space-x-4' },
        zl = (0, n._)(
          'p',
          null,
          'Are you sure you want to make this information as Primary?',
          -1,
        ),
        El = { class: 'text-right space-x-4' },
        Dl = { class: 'p-4 rounded shadow mb-6 bg-white' },
        Al = (0, n._)(
          'h3',
          { class: 'font-semibold text-primary-800 text-lg' },
          'Lead History',
          -1,
        ),
        Ml = { key: 0, class: 'text-center py-3' };
      const Ol = {
        __name: 'Show',
        props: {
          quote: Object,
          leadStatuses: Array,
          ecomDetails: Object,
          membersDetail: Array,
          memberCategories: Array,
          salaryBands: Array,
          nationalities: Array,
          emirates: Array,
          advisors: Array,
          listQuotePlans: Array,
          quoteDocuments: Object,
          documentTypes: Object,
          cdnPath: String,
          ecomHealthInsuranceQuoteUrl: String,
          activities: Array,
          customerAdditionalContacts: Array,
          lostReasons: Array,
          quoteStatusEnum: Object,
          modelType: String,
          notProductionApproval: Boolean,
          allowedDuplicateLOB: Array,
          permissions: Object,
          genderOptions: Object,
          isQuoteDocumentEnabled: Boolean,
          isBetaUser: Boolean,
        },
        setup: function (e) {
          var t = (0, i.qt)(),
            l = (0, p.zn)('toast'),
            v = (0, o.qj)({
              duplicate: !1,
              member: !1,
              memberConfirm: !1,
              doc: !1,
              docConfirm: !1,
              plan: !1,
              createPlan: !1,
              activity: !1,
              activityConfirm: !1,
              addContact: !1,
              contactDeleteConfirm: !1,
              contactPrimaryConfirm: !1,
            }),
            h = (0, i.cI)({
              modelType: 'health',
              parentType: 'health',
              entityId: t.props.quote.id,
              entityCode: t.props.quote.code,
              entityUId: t.props.quote.uid,
              lob_team: [],
              lob_team_sub_selection: null,
            }),
            y = function () {
              (v.duplicate = !0), h.reset();
            },
            Ol = function (e) {
              e &&
                h.post('/quotes/createDuplicate', {
                  preserveScroll: !0,
                  onSuccess: function () {
                    l.success({
                      title: 'Quote duplicated successfully',
                      position: 'top',
                    });
                  },
                  onFinish: function () {
                    v.duplicate = !1;
                  },
                });
            },
            Pl = (0, o.qj)({
              docs: null,
              member: null,
              activity: null,
              contact: null,
            }),
            Ll = (0, o.qj)({ contactPrimary: null }),
            Tl = (0, o.iH)(t.props.quote.health_team_type || ''),
            Rl = (0, o.iH)(null),
            Nl = (0, o.iH)(!1),
            Il = (0, o.iH)(!1),
            jl = (0, o.iH)(null),
            Hl = (0, o.iH)([]),
            Bl = (0, o.iH)(!1),
            Fl = (0, o.iH)(!1),
            Yl = (0, o.iH)(!1),
            Ql = (0, o.iH)(!1),
            Kl = (0, s.VPI)(),
            Zl = Kl.copy,
            Gl = Kl.copied,
            $l = {
              isRequired: function (e) {
                return !!e || 'This field is required';
              },
            },
            Jl = function (e) {
              Zl(e),
                Gl &&
                  l.success({
                    title: 'Link copied to clipboard',
                    position: 'top',
                  });
            },
            Xl = function (e) {
              return (0, n.Fl)(function () {
                return t.props.genderOptions[e];
              });
            },
            en = function (e) {
              return (0, n.Fl)(function () {
                var l;
                return null ===
                  (l = t.props.memberCategories.find(function (t) {
                    return t.id === e;
                  })) || void 0 === l
                  ? void 0
                  : l.text;
              });
            },
            tn = [
              { value: 'RM-NB', label: 'RM-NB' },
              { value: 'RM-Speed', label: 'RM-Speed' },
              { value: 'EBP', label: 'EBP' },
              { value: 'Wow-Call', label: 'Wow-Call' },
              { value: 'No-Type', label: 'No-Type' },
            ],
            ln = (0, n.Fl)(function () {
              return t.props.advisors.map(function (e) {
                return { value: e.id, label: e.name };
              });
            }),
            nn = (0, n.Fl)(function () {
              return Object.keys(t.props.genderOptions).map(function (e) {
                return { value: e, label: t.props.genderOptions[e] };
              });
            }),
            on = (0, n.Fl)(function () {
              return t.props.leadStatuses.map(function (e) {
                return { value: e.id, label: e.text };
              });
            }),
            an = (0, n.Fl)(function () {
              return t.props.nationalities.map(function (e) {
                return { value: e.id, label: e.text };
              });
            }),
            rn = (0, n.Fl)(function () {
              return t.props.memberCategories.map(function (e) {
                return { value: e.id, label: e.text };
              });
            }),
            un = (0, n.Fl)(function () {
              return t.props.emirates.map(function (e) {
                return { value: e.id, label: e.text };
              });
            }),
            sn = (0, n.Fl)(function () {
              return t.props.salaryBands.map(function (e) {
                return { value: e.id, label: e.text };
              });
            }),
            dn = function () {
              Tl.value
                ? i.Nd.post(
                    '/quotes/health/healthTeamAssign',
                    {
                      modelType: 'Health',
                      entityId: t.props.quote.id,
                      assign_team: Tl.value,
                    },
                    {
                      preserveScroll: !0,
                      onBefore: function () {
                        Ql.value = !0;
                      },
                      onSuccess: function () {
                        l.success({ title: 'Team Assigned', position: 'top' });
                      },
                      onFinish: function () {
                        Ql.value = !1;
                      },
                    },
                  )
                : l.error({
                    title: 'Please select a subteam',
                    position: 'top',
                  });
            },
            cn = function () {
              Rl.value
                ? i.Nd.post(
                    '/quotes/health/manualLeadAssign',
                    {
                      modelType: 'Health',
                      entityId: t.props.quote.id,
                      assigned_to_id_new: Rl.value,
                    },
                    {
                      preserveScroll: !0,
                      onBefore: function () {
                        Ql.value = !0;
                      },
                      onSuccess: function () {
                        l.success({ title: 'Lead Assigned', position: 'top' });
                      },
                      onFinish: function () {
                        Ql.value = !1;
                      },
                    },
                  )
                : l.error({ title: 'Please select a lead', position: 'top' });
            },
            mn = (0, i.cI)({
              modelType: 'Health',
              leadId: t.props.quote.id,
              quote_uuid: t.props.quote.uuid,
              assigned_to_user_id: t.props.quote.advisor_id,
              leadStatus: t.props.quote.quote_status_id || null,
              notes: t.props.quote.notes || null,
              trans_code: t.props.quote.transapp_code || null,
              lostReason: t.props.quote.lost_reason || null,
            }),
            pn = function () {
              mn.post(
                '/quotes/Health/'.concat(
                  t.props.quote.id,
                  '/update-lead-status',
                ),
                {
                  preserveScroll: !0,
                  onError: function (e) {
                    console.log(e);
                  },
                  onSuccess: function () {
                    l.success({
                      title: 'Lead Status Updated',
                      position: 'top',
                    });
                  },
                },
              );
            },
            fn = (0, o.qj)({
              isLoading: !1,
              columns: [
                { text: 'Gender', value: 'gender' },
                { text: 'DOB', value: 'dob' },
                { text: 'Nationality', value: 'nationality' },
                { text: 'Emirate of Visa', value: 'emirate' },
                { text: 'Relationship', value: 'member_category_id' },
                { text: 'Action', value: 'action' },
              ],
            }),
            _n = (0, i.cI)({
              id: null,
              gender: null,
              dob: null,
              nationality_id: null,
              salary_band_id: null,
              emirate_of_your_visa_id: null,
              member_category_id: null,
              health_quote_request_id: t.props.quote.id,
            });
          var vn = function () {
              _n.reset(), (Nl.value = !1), (v.member = !0);
            },
            gn = (0, o.iH)(!1),
            wn = function (e) {
              null == _n.nationality_id ? (gn.value = !0) : (gn.value = !1),
                e &&
                  (Nl.value
                    ? _n.put('/members/'.concat(_n.id), {
                        preserveScroll: !0,
                        onSuccess: function () {
                          l.success({
                            title: 'Member Updated',
                            position: 'top',
                          });
                        },
                        onFinish: function () {
                          v.member = !1;
                        },
                      })
                    : _n.post('/members', {
                        preserveScroll: !0,
                        onSuccess: function () {
                          l.success({ title: 'Member Added', position: 'top' });
                        },
                        onFinish: function () {
                          v.member = !1;
                        },
                      }));
            },
            hn = function () {
              _n.delete('/members/'.concat(Pl.member), {
                preserveScroll: !0,
                onSuccess: function () {
                  l.success({ title: 'Member Deleted', position: 'top' });
                },
                onFinish: function () {
                  v.memberConfirm = !1;
                },
              });
            },
            bn = (0, o.qj)({
              isLoading: !1,
              columns: [
                { text: 'Provider Name', value: 'providerName' },
                { text: 'Plan Name', value: 'name' },
                { text: 'Premium with VAT and Basmah', value: 'actualPremium' },
                { text: 'Action', value: 'action' },
              ],
            }),
            yn = function () {
              if (Hl.value.length < 3 || Hl.value.length > 5)
                l.error({
                  title: 'Please select 3 to 5 plans to download PDF.',
                  position: 'top',
                });
              else {
                Bl.value = !0;
                var e = Hl.value.map(function (e) {
                  return e.id;
                });
                f.Z.post(
                  '/api/v1/quotes/health/export-plans-pdf',
                  { plan_ids: e, quote_uuid: t.props.quote.uuid },
                  { responseType: 'json' },
                )
                  .then(function (e) {
                    var t = document.createElement('a'),
                      n = e.data.name;
                    (t.href = e.data.data),
                      t.setAttribute('download', n),
                      document.body.appendChild(t),
                      t.click(),
                      l.success({ title: 'Plans Exported', position: 'top' });
                  })
                  .catch(function (e) {
                    console.log(e);
                  })
                  .finally(function () {
                    Bl.value = !1;
                  });
              }
            },
            xn = function () {
              i.Nd.reload({
                preserveState: !0,
                preserveScroll: !0,
                only: ['listQuotePlans'],
                onStart: function () {
                  v.createPlan = !1;
                },
                onFinish: function () {
                  l.success({ title: 'Plan Created', position: 'top' });
                },
              });
            },
            Un = function () {
              (v.createPlan = !1),
                l.error({ title: 'Plan Creation Failed', position: 'top' });
            },
            Sn = (0, o.qj)({
              isLoading: !1,
              columns: [
                { text: 'Document Type', value: 'document_type_text' },
                { text: 'Document Name', value: 'original_name' },
                { text: 'Created At', value: 'created_at' },
                { text: 'Created By', value: 'created_by_name' },
                { text: 'Action', value: 'action' },
              ],
            }),
            Vn = function () {
              (Sn.isLoading = !0),
                i.Nd.post(
                  '/documents/delete',
                  { docName: Pl.docs, quoteId: t.props.quote.id },
                  {
                    preserveScroll: !0,
                    onFinish: function () {
                      (v.docConfirm = !1),
                        (Sn.isLoading = !1),
                        l.error({ title: 'File Deleted', position: 'top' });
                    },
                  },
                );
            },
            kn = [
              { text: 'Done', value: 'status', width: 60, align: 'center' },
              { text: 'Title', value: 'title' },
              { text: 'Client Name', value: 'client_name' },
              { text: 'Followup Date', value: 'due_date' },
              { text: 'Assigned To', value: 'assignee' },
              { text: 'Action', value: 'action' },
            ],
            qn = (0, i.cI)({
              entityUId: t.props.quote.uuid,
              entityId: t.props.quote.id,
              modelType: 'Health',
              parentType: 'Health',
              quoteType: 3,
              title: null,
              description: null,
              due_date: null,
              assignee_id: null,
              status: null,
              activity_id: null,
              uuid: null,
            }),
            Cn = function () {
              qn.reset(), (Il.value = !1), (v.activity = !0);
            },
            Wn = function (e) {
              e &&
                (Il.value
                  ? qn.post('/activities/'.concat(qn.uuid, '/update'), {
                      preserveScroll: !0,
                      onSuccess: function () {
                        l.success({
                          title: 'Activity Updated',
                          position: 'top',
                        });
                      },
                      onFinish: function () {
                        v.activity = !1;
                      },
                    })
                  : qn.post('/activities/create-activity', {
                      preserveScroll: !0,
                      onSuccess: function () {
                        l.success({ title: 'Activity Added', position: 'top' });
                      },
                      onFinish: function () {
                        v.activity = !1;
                      },
                    }));
            },
            zn = function () {
              i.Nd.post(
                '/activities/'.concat(Pl.activity, '/delete'),
                { isInertia: !0, quote_uuid: t.props.quote.uuid },
                {
                  preserveScroll: !0,
                  onSuccess: function () {
                    l.error({ title: 'Activity Deleted', position: 'top' });
                  },
                  onFinish: function () {
                    v.activityConfirm = !1;
                  },
                },
              );
            },
            En = [
              { text: 'Type', value: 'key' },
              { text: 'Value', value: 'value' },
              { text: 'Created At', value: 'created_at' },
              { text: 'Action', value: 'action' },
            ],
            Dn = (0, i.cI)({
              id: null,
              additional_contact_type: null,
              additional_contact_val: null,
              quote_id: t.props.quote.id,
              customer_id: t.props.quote.customer_id,
              quote_type: 'health',
            }),
            An = function (e) {
              e &&
                Dn.transform(function (e) {
                  return b(b({}, e), {}, { isInertia: !0 });
                }).post('/customer-additional-contact/add', {
                  preserveScroll: !0,
                  onSuccess: function () {
                    l.success({
                      title: 'Additional Contact Added',
                      position: 'top',
                    });
                  },
                  onFinish: function () {
                    v.addContact = !1;
                  },
                });
            },
            Mn = function () {
              i.Nd.post(
                '/customer-additional-contact/'.concat(Pl.contact, '/delete'),
                { isInertia: !0 },
                {
                  preserveScroll: !0,
                  onBefore: function () {
                    Fl.value = !0;
                  },
                  onSuccess: function () {
                    l.error({
                      title: 'Additional Contact Deleted',
                      position: 'top',
                    });
                  },
                  onFinish: function () {
                    (Fl.value = !1), (v.contactDeleteConfirm = !1);
                  },
                },
              );
            },
            On = function () {
              var e = 'email' === Ll.contactPrimary.key;
              i.Nd.post(
                '/customer-additional-contact/'.concat(
                  e ? Ll.contactPrimary.id : 0,
                  '/make-primary',
                ),
                {
                  isInertia: !0,
                  quote_id: t.props.quote.id,
                  key: Ll.contactPrimary.key,
                  value: Ll.contactPrimary.value,
                  quote_type: 'health',
                },
                {
                  preserveScroll: !0,
                  onBefore: function () {
                    Fl.value = !0;
                  },
                  onSuccess: function () {
                    l.success({
                      title: 'Additional Contact Primary',
                      position: 'top',
                    });
                  },
                  onFinish: function () {
                    (Fl.value = !1), (v.contactPrimaryConfirm = !1);
                  },
                },
              );
            },
            Pn = (0, o.iH)(null),
            Ln = (function () {
              var e,
                l =
                  ((e = g().mark(function e() {
                    var l, n;
                    return g().wrap(function (e) {
                      for (;;)
                        switch ((e.prev = e.next)) {
                          case 0:
                            return (
                              (Yl.value = !0),
                              (e.next = 3),
                              fetch(
                                '/quotes/getLeadHistory?modelType=health&recordId='.concat(
                                  t.props.quote.id,
                                ),
                              )
                            );
                          case 3:
                            return (l = e.sent), (e.next = 6), l.json();
                          case 6:
                            (n = e.sent), (Pn.value = n), (Yl.value = !1);
                          case 9:
                          case 'end':
                            return e.stop();
                        }
                    }, e);
                  })),
                  function () {
                    var t = this,
                      l = arguments;
                    return new Promise(function (n, o) {
                      var a = e.apply(t, l);
                      function r(e) {
                        w(a, n, o, r, i, 'next', e);
                      }
                      function i(e) {
                        w(a, n, o, r, i, 'throw', e);
                      }
                      r(void 0);
                    });
                  });
              return function () {
                return l.apply(this, arguments);
              };
            })(),
            Tn = [
              { text: 'Modified At', value: 'ModifiedAt' },
              { text: 'Modified By', value: 'ModifiedBy' },
              { text: 'Notes', value: 'NewNotes' },
              { text: 'Lead Status', value: 'NewStatus' },
            ],
            Rn = function (e) {
              if (e) {
                var t = new Date(e),
                  l = t.getFullYear(),
                  n = '0'.concat(t.getMonth() + 1).slice(-2),
                  o = '0'.concat(t.getDate()).slice(-2);
                return ''.concat(l, '-').concat(n, '-').concat(o);
              }
              return '';
            },
            Nn = (0, i.cI)({
              premium: t.props.quote.premium,
              policy_number: t.props.quote.policy_number || '',
              policy_start_date: Rn(t.props.quote.policy_start_date),
              renewal_expiry_date: Rn(t.props.quote.renewal_expiry_date) || '',
              policy_issuance_date:
                Rn(t.props.quote.policy_issuance_date) || '',
              quote_status_id: t.props.quote.quote_status_id,
              canEdit:
                t.props.quote.quote_status_id ==
                  t.props.quoteStatusEnum.TransactionApproved &&
                t.props.notProductionApproval,
              editMode: !1,
              modelType: t.props.modelType,
              quote_id: t.props.quote.id,
            }),
            In = {
              policy_number: function (e) {
                return (
                  !e ||
                  e.length <= 50 ||
                  'Policy Number should be less than 50 characters'
                );
              },
              policy_start_date: function (e) {
                if (e) {
                  var t = new Date(e);
                  return !isNaN(t.getTime());
                }
                return !0;
              },
              renewal_expiry_date: function (e) {
                if (e) {
                  var t = new Date(e);
                  if (Nn.policy_start_date)
                    if (new Date(Nn.policy_start_date) >= t)
                      return 'Expiry date should be greater than Start Date';
                  return !isNaN(t.getTime());
                }
                return !0;
              },
              premium: function (e) {
                if (e) {
                  var t = parseFloat(e);
                  if (t < 0 || isNaN(t))
                    return 'Premium should be greater than 0';
                }
                return !0;
              },
            },
            jn = function () {
              Nn.editMode = !1;
            },
            Hn = function (e) {
              e &&
                Nn.transform(function (e) {
                  return {
                    quote_policy_number: e.policy_number,
                    quote_policy_start_date: e.policy_start_date,
                    quote_policy_expiry_date: e.renewal_expiry_date,
                    quote_policy_issuance_date: e.policy_issuance_date,
                    quote_premium: e.premium,
                    modelType: e.modelType,
                    quote_id: e.quote_id,
                    isInertia: !0,
                  };
                }).post(
                  '/quotes/'.concat(t.props.modelType, '/update-quote-policy'),
                  {
                    preserveScroll: !0,
                    onSuccess: function () {
                      l.success({
                        title: 'Policy Details Updated',
                        position: 'top',
                      });
                    },
                    onFinish: function () {
                      Nn.editMode = !1;
                    },
                  },
                );
            };
          return (
            (0, n.bv)(function () {
              var e = t.props.advisors.find(function (e) {
                return e.id == t.props.quote.advisor_id;
              });
              e && (Rl.value = e.id);
            }),
            function (t, s) {
              var p,
                f = (0, n.up)('x-button'),
                g = (0, n.up)('x-select'),
                w = (0, n.up)('x-form'),
                b = (0, n.up)('x-modal'),
                Kl = (0, n.up)('x-divider'),
                Zl = (0, n.up)('x-tag'),
                Gl = (0, n.up)('DataTable'),
                Rn = (0, n.up)('x-input'),
                Bn = (0, n.up)('x-textarea'),
                Fn = (0, n.up)('x-checkbox');
              return (
                (0, n.wg)(),
                (0, n.iD)('div', null, [
                  (0, n.Wm)((0, o.SU)(i.Fb), { title: 'Health Detail' }),
                  (0, n._)('div', x, [
                    U,
                    (0, n._)('div', S, [
                      (0, n.Wm)(
                        f,
                        {
                          size: 'sm',
                          color: '#ff5e00',
                          onClick: (0, a.iM)(y, ['prevent']),
                        },
                        {
                          default: (0, n.w5)(function () {
                            return [(0, n.Uk)(' Duplicate Lead ')];
                          }),
                          _: 1,
                        },
                        8,
                        ['onClick'],
                      ),
                      (0, n.Wm)(
                        (0, o.SU)(i.rU),
                        { href: '/quotes/health', 'preserve-scroll': '' },
                        {
                          default: (0, n.w5)(function () {
                            return [
                              (0, n.Wm)(
                                f,
                                { size: 'sm', color: 'primary', tag: 'div' },
                                {
                                  default: (0, n.w5)(function () {
                                    return [(0, n.Uk)(' Health List ')];
                                  }),
                                  _: 1,
                                },
                              ),
                            ];
                          }),
                          _: 1,
                        },
                      ),
                      (0, n.Wm)(
                        (0, o.SU)(i.rU),
                        { href: ''.concat(e.quote.uuid, '/edit') },
                        {
                          default: (0, n.w5)(function () {
                            return [
                              (0, n.Wm)(
                                f,
                                { size: 'sm', tag: 'div' },
                                {
                                  default: (0, n.w5)(function () {
                                    return [(0, n.Uk)('Edit')];
                                  }),
                                  _: 1,
                                },
                              ),
                            ];
                          }),
                          _: 1,
                        },
                        8,
                        ['href'],
                      ),
                    ]),
                  ]),
                  (0, n.Wm)(
                    b,
                    {
                      modelValue: v.duplicate,
                      'onUpdate:modelValue':
                        s[2] ||
                        (s[2] = function (e) {
                          return (v.duplicate = e);
                        }),
                      size: 'lg',
                      'show-close': '',
                      backdrop: '',
                    },
                    {
                      header: (0, n.w5)(function () {
                        return [(0, n.Uk)(' Duplicate Lead ')];
                      }),
                      default: (0, n.w5)(function () {
                        return [
                          (0, n.Wm)(
                            w,
                            { onSubmit: Ol, 'auto-focus': !1 },
                            {
                              default: (0, n.w5)(function () {
                                return [
                                  (0, n._)('div', V, [
                                    (0, n.Wm)(
                                      g,
                                      {
                                        modelValue: (0, o.SU)(h).lob_team,
                                        'onUpdate:modelValue':
                                          s[0] ||
                                          (s[0] = function (e) {
                                            return ((0, o.SU)(h).lob_team = e);
                                          }),
                                        label: 'LOBs',
                                        options: e.allowedDuplicateLOB.map(
                                          function (e) {
                                            return { value: e, label: e };
                                          },
                                        ),
                                        rules: [$l.isRequired],
                                        placeholder:
                                          'Select LOB For Duplication',
                                        class: 'w-full',
                                        multiple: '',
                                      },
                                      null,
                                      8,
                                      ['modelValue', 'options', 'rules'],
                                    ),
                                    (0, n.Wm)(
                                      g,
                                      {
                                        modelValue: (0, o.SU)(h)
                                          .lob_team_sub_selection,
                                        'onUpdate:modelValue':
                                          s[1] ||
                                          (s[1] = function (e) {
                                            return ((0, o.SU)(
                                              h,
                                            ).lob_team_sub_selection = e);
                                          }),
                                        label: 'Reason',
                                        rules: [$l.isRequired],
                                        class: 'w-full',
                                        options: [
                                          {
                                            value: 'new_enquiry',
                                            label: 'New enquiry',
                                          },
                                          {
                                            value: 'record_only',
                                            label: 'Record purposes only',
                                          },
                                        ],
                                      },
                                      null,
                                      8,
                                      ['modelValue', 'rules'],
                                    ),
                                    (0, n.Wm)(
                                      f,
                                      {
                                        color: 'orange',
                                        type: 'submit',
                                        loading: (0, o.SU)(h).processing,
                                      },
                                      {
                                        default: (0, n.w5)(function () {
                                          return [
                                            (0, n.Uk)(' Create Duplicate '),
                                          ];
                                        }),
                                        _: 1,
                                      },
                                      8,
                                      ['loading'],
                                    ),
                                  ]),
                                ];
                              }),
                              _: 1,
                            },
                          ),
                        ];
                      }),
                      _: 1,
                    },
                    8,
                    ['modelValue'],
                  ),
                  (0, n.Wm)(Kl, { class: 'my-4' }),
                  (0, n._)('div', k, [
                    (0, n._)('div', q, [
                      (0, n._)('div', C, [
                        (0, n.Wm)(
                          g,
                          {
                            modelValue: Tl.value,
                            'onUpdate:modelValue':
                              s[3] ||
                              (s[3] = function (e) {
                                return (Tl.value = e);
                              }),
                            label: 'Assign Subteam',
                            options: tn,
                            placeholder: 'Select Subteam',
                            class: 'w-auto flex-1',
                          },
                          null,
                          8,
                          ['modelValue'],
                        ),
                        (0, n._)('div', null, [
                          (0, n.Wm)(
                            f,
                            {
                              color: 'orange',
                              size: 'sm',
                              onClick: (0, a.iM)(dn, ['prevent']),
                              loading: Ql.value,
                            },
                            {
                              default: (0, n.w5)(function () {
                                return [(0, n.Uk)(' Assign Team ')];
                              }),
                              _: 1,
                            },
                            8,
                            ['onClick', 'loading'],
                          ),
                        ]),
                      ]),
                      (0, n._)('div', W, [
                        (0, n.Wm)(
                          g,
                          {
                            modelValue: Rl.value,
                            'onUpdate:modelValue':
                              s[4] ||
                              (s[4] = function (e) {
                                return (Rl.value = e);
                              }),
                            label: 'Assign Lead',
                            options: (0, o.SU)(ln),
                            placeholder: 'Select Lead',
                            class: 'w-auto flex-1',
                          },
                          null,
                          8,
                          ['modelValue', 'options'],
                        ),
                        (0, n._)('div', null, [
                          (0, n.Wm)(
                            f,
                            {
                              color: 'orange',
                              size: 'sm',
                              onClick: (0, a.iM)(cn, ['prevent']),
                              loading: Ql.value,
                            },
                            {
                              default: (0, n.w5)(function () {
                                return [(0, n.Uk)(' Assign ')];
                              }),
                              _: 1,
                            },
                            8,
                            ['onClick', 'loading'],
                          ),
                        ]),
                      ]),
                    ]),
                  ]),
                  (0, n._)('div', z, [
                    (0, n._)('div', E, [
                      (0, n._)('dl', D, [
                        (0, n._)('div', A, [
                          M,
                          (0, n._)('dd', null, (0, r.zw)(e.quote.code), 1),
                        ]),
                        (0, n._)('div', O, [
                          P,
                          (0, n._)(
                            'dd',
                            null,
                            (0, r.zw)(e.quote.created_at),
                            1,
                          ),
                        ]),
                        (0, n._)('div', L, [
                          T,
                          (0, n._)(
                            'dd',
                            null,
                            (0, r.zw)(e.quote.health_team_type),
                            1,
                          ),
                        ]),
                        (0, n._)('div', R, [
                          N,
                          (0, n._)(
                            'dd',
                            null,
                            (0, r.zw)(e.quote.advisor_id_text),
                            1,
                          ),
                        ]),
                        (0, n._)('div', I, [
                          j,
                          (0, n._)('dd', null, (0, r.zw)(e.quote.source), 1),
                        ]),
                        (0, n._)('div', H, [
                          B,
                          (0, n._)(
                            'dd',
                            null,
                            (0, r.zw)(e.quote.updated_at),
                            1,
                          ),
                        ]),
                        (0, n._)('div', F, [
                          Y,
                          (0, n._)(
                            'dd',
                            null,
                            (0, r.zw)(e.quote.parent_duplicate_quote_id),
                            1,
                          ),
                        ]),
                        (0, n._)('div', Q, [
                          K,
                          (0, n._)(
                            'dd',
                            null,
                            (0, r.zw)(e.quote.is_ecommerce ? 'Yes' : 'No'),
                            1,
                          ),
                        ]),
                        (0, n._)('div', Z, [
                          G,
                          (0, n._)(
                            'dd',
                            null,
                            (0, r.zw)(e.quote.is_ebp_renewal ? 'Yes' : 'No'),
                            1,
                          ),
                        ]),
                        (0, n._)('div', $, [
                          J,
                          (0, n._)(
                            'dd',
                            null,
                            (0, r.zw)(e.quote.renewal_batch),
                            1,
                          ),
                        ]),
                        (0, n._)('div', X, [
                          ee,
                          (0, n._)(
                            'dd',
                            null,
                            (0, r.zw)(e.quote.lost_reason),
                            1,
                          ),
                        ]),
                        (0, n._)('div', te, [
                          le,
                          (0, n._)('dd', null, (0, r.zw)(e.quote.device), 1),
                        ]),
                      ]),
                    ]),
                    (0, n._)('div', ne, [
                      oe,
                      (0, n.Wm)(Kl, { class: 'mb-4 mt-1' }),
                    ]),
                    (0, n._)('div', ae, [
                      (0, n._)('dl', re, [
                        (0, n._)('div', ie, [
                          ue,
                          (0, n._)(
                            'dd',
                            null,
                            (0, r.zw)(e.quote.first_name),
                            1,
                          ),
                        ]),
                        (0, n._)('div', se, [
                          de,
                          (0, n._)('dd', null, (0, r.zw)(e.quote.last_name), 1),
                        ]),
                        (0, n._)('div', ce, [
                          me,
                          (0, n._)('dd', null, (0, r.zw)(e.quote.mobile_no), 1),
                        ]),
                        (0, n._)('div', pe, [
                          fe,
                          (0, n._)('dd', null, (0, r.zw)(e.quote.email), 1),
                        ]),
                        (0, n._)('div', _e, [
                          ve,
                          (0, n._)(
                            'dd',
                            null,
                            (0, r.zw)(Xl(e.quote.gender).value),
                            1,
                          ),
                        ]),
                        (0, n._)('div', ge, [
                          we,
                          (0, n._)(
                            'dd',
                            null,
                            (0, r.zw)(e.quote.marital_status_id_text),
                            1,
                          ),
                        ]),
                        (0, n._)('div', he, [
                          be,
                          (0, n._)(
                            'dd',
                            null,
                            (0, r.zw)(e.quote.nationality_id_text),
                            1,
                          ),
                        ]),
                        (0, n._)('div', ye, [
                          xe,
                          (0, n._)('dd', null, (0, r.zw)(e.quote.dob), 1),
                        ]),
                        (0, n._)('div', Ue, [
                          Se,
                          (0, n._)(
                            'dd',
                            null,
                            (0, r.zw)(e.quote.emirate_of_your_visa_id_text),
                            1,
                          ),
                        ]),
                        (0, n._)('div', Ve, [
                          ke,
                          (0, n._)(
                            'dd',
                            null,
                            (0, r.zw)(e.quote.member_category_id_text),
                            1,
                          ),
                        ]),
                        (0, n._)('div', qe, [
                          Ce,
                          (0, n._)(
                            'dd',
                            null,
                            (0, r.zw)(e.quote.salary_band_id_text),
                            1,
                          ),
                        ]),
                      ]),
                    ]),
                    (0, n._)('div', We, [
                      ze,
                      (0, n.Wm)(Kl, { class: 'mb-4 mt-1' }),
                    ]),
                    (0, n._)('div', Ee, [
                      (0, n._)('dl', De, [
                        (0, n._)('div', Ae, [
                          Me,
                          (0, n._)(
                            'dd',
                            null,
                            (0, r.zw)(e.quote.cover_for_id_text),
                            1,
                          ),
                        ]),
                        (0, n._)('div', Oe, [
                          Pe,
                          (0, n._)(
                            'dd',
                            null,
                            (0, r.zw)(e.quote.currently_insured_with_id_text),
                            1,
                          ),
                        ]),
                        (0, n._)('div', Le, [
                          Te,
                          (0, n._)('dd', null, (0, r.zw)(e.quote.plan_id), 1),
                        ]),
                        (0, n._)('div', Re, [
                          Ne,
                          (0, n._)(
                            'dd',
                            null,
                            (0, r.zw)(e.quote.next_followup_date),
                            1,
                          ),
                        ]),
                        (0, n._)('div', Ie, [
                          je,
                          (0, n._)('dd', null, (0, r.zw)(e.quote.details), 1),
                        ]),
                      ]),
                    ]),
                    (0, n._)('div', He, [
                      Be,
                      (0, n.Wm)(Kl, { class: 'mb-4 mt-1' }),
                    ]),
                    (0, n._)('div', Fe, [
                      (0, n._)('dl', Ye, [
                        (0, n._)('div', Qe, [
                          Ke,
                          (0, n._)(
                            'dd',
                            null,
                            (0, r.zw)(e.quote.previous_quote_policy_number),
                            1,
                          ),
                        ]),
                        (0, n._)('div', Ze, [
                          Ge,
                          (0, n._)(
                            'dd',
                            null,
                            (0, r.zw)(e.quote.previous_quote_policy_premium),
                            1,
                          ),
                        ]),
                        (0, n._)('div', $e, [
                          Je,
                          (0, n._)(
                            'dd',
                            null,
                            (0, r.zw)(e.quote.previous_policy_expiry_date),
                            1,
                          ),
                        ]),
                      ]),
                    ]),
                    (0, n._)('div', Xe, [
                      et,
                      (0, n.Wm)(Kl, { class: 'mb-4 mt-1' }),
                    ]),
                    (0, n._)('div', tt, [
                      (0, n._)('dl', lt, [
                        (0, n._)('div', nt, [
                          ot,
                          (0, n._)(
                            'dd',
                            null,
                            (0, r.zw)(e.quote.policy_number),
                            1,
                          ),
                        ]),
                        (0, n._)('div', at, [
                          rt,
                          (0, n._)(
                            'dd',
                            null,
                            (0, r.zw)(e.quote.policy_start_date),
                            1,
                          ),
                        ]),
                        (0, n._)('div', it, [
                          ut,
                          (0, n._)(
                            'dd',
                            null,
                            (0, r.zw)(e.quote.policy_issuance_date),
                            1,
                          ),
                        ]),
                        (0, n._)('div', st, [
                          dt,
                          (0, n._)('dd', null, (0, r.zw)(e.quote.premium), 1),
                        ]),
                        (0, n._)('div', ct, [
                          mt,
                          (0, n._)(
                            'dd',
                            null,
                            (0, r.zw)(e.quote.transapp_code),
                            1,
                          ),
                        ]),
                      ]),
                    ]),
                  ]),
                  (0, n._)('div', pt, [
                    (0, n._)('div', ft, [
                      (0, n._)('h3', _t, [
                        (0, n.Uk)(' Member Details '),
                        (0, n.Wm)(
                          Zl,
                          { size: 'sm' },
                          {
                            default: (0, n.w5)(function () {
                              return [
                                (0, n.Uk)(
                                  (0, r.zw)(e.membersDetail.length || 0),
                                  1,
                                ),
                              ];
                            }),
                            _: 1,
                          },
                        ),
                      ]),
                      (0, n.Wm)(
                        f,
                        {
                          onClick: (0, a.iM)(vn, ['prevent']),
                          size: 'sm',
                          color: 'orange',
                        },
                        {
                          default: (0, n.w5)(function () {
                            return [(0, n.Uk)(' Add Member ')];
                          }),
                          _: 1,
                        },
                        8,
                        ['onClick'],
                      ),
                    ]),
                    (0, n.Wm)(
                      Gl,
                      {
                        'table-class-name': 'tablefixed compact',
                        headers: fn.columns,
                        items: e.membersDetail || [],
                        'show-index': '',
                        'border-cell': '',
                        'hide-rows-per-page': '',
                        'hide-footer': '',
                      },
                      {
                        'item-index': (0, n.w5)(function (e) {
                          var t = e.index;
                          return [
                            (0, n._)('div', null, 'Member ' + (0, r.zw)(t), 1),
                          ];
                        }),
                        'item-gender': (0, n.w5)(function (e) {
                          var t = e.gender;
                          return [(0, n.Uk)((0, r.zw)(Xl(t).value), 1)];
                        }),
                        'item-dob': (0, n.w5)(function (e) {
                          var t,
                            l = e.dob;
                          return [
                            (0, n.Uk)(
                              (0, r.zw)(
                                ((t = l), (0, u.WJ)(t, 'DD/MM/YYYY')).value,
                              ),
                              1,
                            ),
                          ];
                        }),
                        'item-nationality': (0, n.w5)(function (e) {
                          var t = e.nationality;
                          return [
                            (0, n.Uk)(
                              (0, r.zw)(null == t ? void 0 : t.text),
                              1,
                            ),
                          ];
                        }),
                        'item-emirate': (0, n.w5)(function (e) {
                          var t = e.emirate;
                          return [
                            (0, n.Uk)(
                              (0, r.zw)(null == t ? void 0 : t.text),
                              1,
                            ),
                          ];
                        }),
                        'item-member_category_id': (0, n.w5)(function (e) {
                          var t = e.member_category_id;
                          return [(0, n.Uk)((0, r.zw)(en(t).value), 1)];
                        }),
                        'item-action': (0, n.w5)(function (e) {
                          return [
                            (0, n._)('div', vt, [
                              (0, n.Wm)(
                                f,
                                {
                                  size: 'xs',
                                  color: 'primary',
                                  outlined: '',
                                  onClick: (0, a.iM)(
                                    function (t) {
                                      return (
                                        (l = e),
                                        (Nl.value = !0),
                                        (v.member = !0),
                                        (_n.id = l.id),
                                        (_n.gender = l.gender),
                                        (_n.dob = l.dob),
                                        (_n.nationality_id = l.nationality_id),
                                        (_n.emirate_of_your_visa_id =
                                          l.emirate_of_your_visa_id),
                                        (_n.member_category_id =
                                          l.member_category_id),
                                        void (_n.salary_band_id =
                                          l.salary_band_id)
                                      );
                                      var l;
                                    },
                                    ['prevent'],
                                  ),
                                },
                                {
                                  default: (0, n.w5)(function () {
                                    return [(0, n.Uk)(' Edit ')];
                                  }),
                                  _: 2,
                                },
                                1032,
                                ['onClick'],
                              ),
                              (0, n.Wm)(
                                f,
                                {
                                  size: 'xs',
                                  color: 'error',
                                  outlined: '',
                                  onClick: (0, a.iM)(
                                    function (t) {
                                      return (
                                        (l = e.id),
                                        (v.memberConfirm = !0),
                                        void (Pl.member = l)
                                      );
                                      var l;
                                    },
                                    ['prevent'],
                                  ),
                                },
                                {
                                  default: (0, n.w5)(function () {
                                    return [(0, n.Uk)(' Delete ')];
                                  }),
                                  _: 2,
                                },
                                1032,
                                ['onClick'],
                              ),
                            ]),
                          ];
                        }),
                        _: 1,
                      },
                      8,
                      ['headers', 'items'],
                    ),
                    (0, n.Wm)(
                      b,
                      {
                        modelValue: v.member,
                        'onUpdate:modelValue':
                          s[12] ||
                          (s[12] = function (e) {
                            return (v.member = e);
                          }),
                        size: 'lg',
                        'show-close': '',
                        backdrop: '',
                      },
                      {
                        header: (0, n.w5)(function () {
                          return [
                            (0, n.Uk)(
                              (0, r.zw)(Nl.value ? 'Edit' : 'Add') + ' Member ',
                              1,
                            ),
                          ];
                        }),
                        default: (0, n.w5)(function () {
                          return [
                            (0, n.Wm)(
                              w,
                              { onSubmit: wn, 'auto-focus': !1 },
                              {
                                default: (0, n.w5)(function () {
                                  return [
                                    (0, n._)('div', gt, [
                                      (0, n._)(
                                        'input',
                                        {
                                          type: 'hidden',
                                          value: (0, o.SU)(_n).id,
                                        },
                                        null,
                                        8,
                                        wt,
                                      ),
                                      (0, n.Wm)(
                                        _.Z,
                                        {
                                          modelValue: (0, o.SU)(_n)
                                            .nationality_id,
                                          'onUpdate:modelValue':
                                            s[5] ||
                                            (s[5] = function (e) {
                                              return ((0, o.SU)(
                                                _n,
                                              ).nationality_id = e);
                                            }),
                                          label: 'Nationality',
                                          options: (0, o.SU)(an),
                                          placeholder: 'Select Nationality',
                                          single: !0,
                                          hasError: gn.value,
                                        },
                                        null,
                                        8,
                                        ['modelValue', 'options', 'hasError'],
                                      ),
                                      (0, n.Wm)(
                                        g,
                                        {
                                          modelValue: (0, o.SU)(_n)
                                            .emirate_of_your_visa_id,
                                          'onUpdate:modelValue':
                                            s[6] ||
                                            (s[6] = function (e) {
                                              return ((0, o.SU)(
                                                _n,
                                              ).emirate_of_your_visa_id = e);
                                            }),
                                          label: 'Emirate of Visa',
                                          options: (0, o.SU)(un),
                                          rules: [$l.isRequired],
                                          placeholder: 'Select Emirate of Visa',
                                          class: 'w-full',
                                        },
                                        null,
                                        8,
                                        ['modelValue', 'options', 'rules'],
                                      ),
                                      (0, n.Wm)(
                                        g,
                                        {
                                          modelValue: (0, o.SU)(_n).gender,
                                          'onUpdate:modelValue':
                                            s[7] ||
                                            (s[7] = function (e) {
                                              return ((0, o.SU)(_n).gender = e);
                                            }),
                                          label: 'Gender',
                                          options: (0, o.SU)(nn),
                                          rules: [$l.isRequired],
                                          placeholder: 'Select Gender',
                                          class: 'w-full',
                                        },
                                        null,
                                        8,
                                        ['modelValue', 'options', 'rules'],
                                      ),
                                      (0, n.Wm)(
                                        Rn,
                                        {
                                          modelValue: (0, o.SU)(_n).dob,
                                          'onUpdate:modelValue':
                                            s[8] ||
                                            (s[8] = function (e) {
                                              return ((0, o.SU)(_n).dob = e);
                                            }),
                                          label: 'DOB',
                                          type: 'date',
                                          rules: [$l.isRequired],
                                          class: 'w-full',
                                        },
                                        null,
                                        8,
                                        ['modelValue', 'rules'],
                                      ),
                                      (0, n.Wm)(
                                        g,
                                        {
                                          modelValue: (0, o.SU)(_n)
                                            .member_category_id,
                                          'onUpdate:modelValue':
                                            s[9] ||
                                            (s[9] = function (e) {
                                              return ((0, o.SU)(
                                                _n,
                                              ).member_category_id = e);
                                            }),
                                          label: 'Relationship',
                                          options: (0, o.SU)(rn),
                                          rules: [$l.isRequired],
                                          placeholder: 'Select Relationship',
                                          class: 'w-full',
                                        },
                                        null,
                                        8,
                                        ['modelValue', 'options', 'rules'],
                                      ),
                                      (0, n.Wm)(
                                        g,
                                        {
                                          modelValue: (0, o.SU)(_n)
                                            .salary_band_id,
                                          'onUpdate:modelValue':
                                            s[10] ||
                                            (s[10] = function (e) {
                                              return ((0, o.SU)(
                                                _n,
                                              ).salary_band_id = e);
                                            }),
                                          label: 'Salary Band',
                                          options: (0, o.SU)(sn),
                                          placeholder: 'Select Salary Band',
                                          class: 'w-full',
                                        },
                                        null,
                                        8,
                                        ['modelValue', 'options'],
                                      ),
                                    ]),
                                    (0, n._)('div', ht, [
                                      (0, n.Wm)(
                                        f,
                                        {
                                          size: 'sm',
                                          onClick:
                                            s[11] ||
                                            (s[11] = (0, a.iM)(
                                              function (e) {
                                                return (v.member = !1);
                                              },
                                              ['prevent'],
                                            )),
                                        },
                                        {
                                          default: (0, n.w5)(function () {
                                            return [(0, n.Uk)(' Cancel ')];
                                          }),
                                          _: 1,
                                        },
                                      ),
                                      (0, n.Wm)(
                                        f,
                                        {
                                          size: 'sm',
                                          color: 'emerald',
                                          loading: (0, o.SU)(_n).processing,
                                          type: 'submit',
                                        },
                                        {
                                          default: (0, n.w5)(function () {
                                            return [
                                              (0, n.Uk)(
                                                (0, r.zw)(
                                                  Nl.value ? 'Update' : 'Save',
                                                ),
                                                1,
                                              ),
                                            ];
                                          }),
                                          _: 1,
                                        },
                                        8,
                                        ['loading'],
                                      ),
                                    ]),
                                  ];
                                }),
                                _: 1,
                              },
                            ),
                          ];
                        }),
                        _: 1,
                      },
                      8,
                      ['modelValue'],
                    ),
                    (0, n.Wm)(
                      b,
                      {
                        modelValue: v.memberConfirm,
                        'onUpdate:modelValue':
                          s[14] ||
                          (s[14] = function (e) {
                            return (v.memberConfirm = e);
                          }),
                        'show-close': '',
                        backdrop: '',
                      },
                      {
                        header: (0, n.w5)(function () {
                          return [(0, n.Uk)(' Delete Member Detail ')];
                        }),
                        actions: (0, n.w5)(function () {
                          return [
                            (0, n._)('div', yt, [
                              (0, n.Wm)(
                                f,
                                {
                                  size: 'sm',
                                  ghost: '',
                                  onClick:
                                    s[13] ||
                                    (s[13] = (0, a.iM)(
                                      function (e) {
                                        return (v.memberConfirm = !1);
                                      },
                                      ['prevent'],
                                    )),
                                },
                                {
                                  default: (0, n.w5)(function () {
                                    return [(0, n.Uk)(' Cancel ')];
                                  }),
                                  _: 1,
                                },
                              ),
                              (0, n.Wm)(
                                f,
                                {
                                  size: 'sm',
                                  color: 'error',
                                  onClick: (0, a.iM)(hn, ['prevent']),
                                  loading: (0, o.SU)(_n).processing,
                                },
                                {
                                  default: (0, n.w5)(function () {
                                    return [(0, n.Uk)(' Delete ')];
                                  }),
                                  _: 1,
                                },
                                8,
                                ['onClick', 'loading'],
                              ),
                            ]),
                          ];
                        }),
                        default: (0, n.w5)(function () {
                          return [bt];
                        }),
                        _: 1,
                      },
                      8,
                      ['modelValue'],
                    ),
                  ]),
                  (0, n._)('div', xt, [
                    (0, n._)('div', null, [
                      Ut,
                      (0, n.Wm)(Kl, { class: 'mb-4 mt-1' }),
                    ]),
                    (0, n._)('div', St, [
                      (0, n._)('div', Vt, [
                        (0, n.Wm)(
                          Bn,
                          {
                            modelValue: (0, o.SU)(mn).notes,
                            'onUpdate:modelValue':
                              s[15] ||
                              (s[15] = function (e) {
                                return ((0, o.SU)(mn).notes = e);
                              }),
                            type: 'text',
                            label: 'Notes',
                            placeholder: 'Lead Notes',
                            class: 'w-full',
                            disabled: 15 == e.quote.quote_status_id,
                          },
                          null,
                          8,
                          ['modelValue', 'disabled'],
                        ),
                      ]),
                      (0, n._)('div', kt, [
                        (0, n._)('div', qt, [
                          (0, n.Wm)(
                            g,
                            {
                              modelValue: (0, o.SU)(mn).leadStatus,
                              'onUpdate:modelValue':
                                s[16] ||
                                (s[16] = function (e) {
                                  return ((0, o.SU)(mn).leadStatus = e);
                                }),
                              label: 'Status',
                              options: (0, o.SU)(on),
                              disabled: 15 == e.quote.quote_status_id,
                              placeholder: 'Lead Status',
                              class: 'w-full',
                            },
                            null,
                            8,
                            ['modelValue', 'options', 'disabled'],
                          ),
                          15 == (0, o.SU)(mn).leadStatus
                            ? ((0, n.wg)(),
                              (0, n.j4)(
                                Rn,
                                {
                                  key: 0,
                                  modelValue: (0, o.SU)(mn).trans_code,
                                  'onUpdate:modelValue':
                                    s[17] ||
                                    (s[17] = function (e) {
                                      return ((0, o.SU)(mn).trans_code = e);
                                    }),
                                  label: 'TransApp Code',
                                  placeholder: 'TransApp Code is required',
                                  class: 'w-full',
                                  error: (0, o.SU)(mn).errors.trans_code,
                                },
                                null,
                                8,
                                ['modelValue', 'error'],
                              ))
                            : (0, n.kq)('', !0),
                          17 == (0, o.SU)(mn).leadStatus
                            ? ((0, n.wg)(),
                              (0, n.j4)(
                                g,
                                {
                                  key: 1,
                                  modelValue: (0, o.SU)(mn).lostReason,
                                  'onUpdate:modelValue':
                                    s[18] ||
                                    (s[18] = function (e) {
                                      return ((0, o.SU)(mn).lostReason = e);
                                    }),
                                  label: 'Lost Reason',
                                  options:
                                    null === (p = e.lostReasons) || void 0 === p
                                      ? void 0
                                      : p.map(function (e) {
                                          return { value: e.id, label: e.text };
                                        }),
                                  placeholder: 'Lost Reason is required',
                                  class: 'w-full',
                                  error: (0, o.SU)(mn).errors.lostReason,
                                },
                                null,
                                8,
                                ['modelValue', 'options', 'error'],
                              ))
                            : (0, n.kq)('', !0),
                        ]),
                        (0, n._)('div', Ct, [
                          (0, n.Wm)(
                            f,
                            {
                              class: 'mt-4',
                              color: 'emerald',
                              size: 'sm',
                              loading: (0, o.SU)(mn).processing,
                              onClick: (0, a.iM)(pn, ['prevent']),
                            },
                            {
                              default: (0, n.w5)(function () {
                                return [(0, n.Uk)(' Change Status ')];
                              }),
                              _: 1,
                            },
                            8,
                            ['loading', 'onClick'],
                          ),
                        ]),
                      ]),
                    ]),
                  ]),
                  (0, n._)('div', Wt, [
                    (0, n._)('div', null, [
                      zt,
                      (0, n.Wm)(Kl, { class: 'mb-4 mt-1' }),
                    ]),
                    (0, n._)('div', Et, [
                      (0, n._)('dl', Dt, [
                        (0, n._)('div', At, [
                          Mt,
                          (0, n._)(
                            'dd',
                            null,
                            (0, r.zw)(e.ecomDetails.planName),
                            1,
                          ),
                        ]),
                        (0, n._)('div', Ot, [
                          Pt,
                          (0, n._)(
                            'dd',
                            null,
                            (0, r.zw)(e.ecomDetails.providerName),
                            1,
                          ),
                        ]),
                        (0, n._)('div', Lt, [
                          Tt,
                          (0, n._)(
                            'dd',
                            null,
                            (0, r.zw)(e.ecomDetails.paymentStatus),
                            1,
                          ),
                        ]),
                        (0, n._)('div', Rt, [
                          Nt,
                          (0, n._)(
                            'dd',
                            null,
                            (0, r.zw)(e.ecomDetails.paidAt),
                            1,
                          ),
                        ]),
                        (0, n._)('div', It, [
                          jt,
                          (0, n._)(
                            'dd',
                            null,
                            (0, r.zw)(e.ecomDetails.network),
                            1,
                          ),
                        ]),
                      ]),
                    ]),
                  ]),
                  e.isQuoteDocumentEnabled
                    ? ((0, n.wg)(),
                      (0, n.iD)('div', Ht, [
                        (0, n._)('div', null, [
                          Bt,
                          (0, n.Wm)(Kl, { class: 'mb-4 mt-1' }),
                        ]),
                        (0, n.Wm)(
                          w,
                          { onSubmit: Hn, 'auto-focus': !1 },
                          {
                            default: (0, n.w5)(function () {
                              return [
                                (0, n._)('div', Ft, [
                                  (0, n._)('div', Yt, [
                                    (0, n.Wm)(
                                      Rn,
                                      {
                                        modelValue: (0, o.SU)(Nn).policy_number,
                                        'onUpdate:modelValue':
                                          s[19] ||
                                          (s[19] = function (e) {
                                            return ((0, o.SU)(
                                              Nn,
                                            ).policy_number = e);
                                          }),
                                        disabled: !(0, o.SU)(Nn).editMode,
                                        label: 'Policy Number',
                                        rules: [
                                          $l.isRequired,
                                          In.policy_number,
                                        ],
                                        class: 'w-full',
                                      },
                                      null,
                                      8,
                                      ['modelValue', 'disabled', 'rules'],
                                    ),
                                  ]),
                                  (0, n._)('div', Qt, [
                                    (0, n.Wm)(
                                      Rn,
                                      {
                                        modelValue: (0, o.SU)(Nn)
                                          .policy_issuance_date,
                                        'onUpdate:modelValue':
                                          s[20] ||
                                          (s[20] = function (e) {
                                            return ((0, o.SU)(
                                              Nn,
                                            ).policy_issuance_date = e);
                                          }),
                                        disabled: !(0, o.SU)(Nn).editMode,
                                        type: 'date',
                                        label: 'Issuance Date',
                                        rules: [$l.isRequired],
                                        class: 'w-full',
                                      },
                                      null,
                                      8,
                                      ['modelValue', 'disabled', 'rules'],
                                    ),
                                  ]),
                                ]),
                                (0, n._)('div', Kt, [
                                  (0, n._)('div', Zt, [
                                    (0, n.Wm)(
                                      Rn,
                                      {
                                        modelValue: (0, o.SU)(Nn)
                                          .policy_start_date,
                                        'onUpdate:modelValue':
                                          s[21] ||
                                          (s[21] = function (e) {
                                            return ((0, o.SU)(
                                              Nn,
                                            ).policy_start_date = e);
                                          }),
                                        disabled: !(0, o.SU)(Nn).editMode,
                                        type: 'date',
                                        label: 'Start Date',
                                        rules: [
                                          $l.isRequired,
                                          In.policy_start_date,
                                        ],
                                        class: 'w-full',
                                      },
                                      null,
                                      8,
                                      ['modelValue', 'disabled', 'rules'],
                                    ),
                                  ]),
                                  (0, n._)('div', Gt, [
                                    (0, n.Wm)(
                                      Rn,
                                      {
                                        modelValue: (0, o.SU)(Nn)
                                          .renewal_expiry_date,
                                        'onUpdate:modelValue':
                                          s[22] ||
                                          (s[22] = function (e) {
                                            return ((0, o.SU)(
                                              Nn,
                                            ).renewal_expiry_date = e);
                                          }),
                                        disabled: !(0, o.SU)(Nn).editMode,
                                        type: 'date',
                                        label: 'Expiry Date',
                                        rules: [
                                          $l.isRequired,
                                          In.renewal_expiry_date,
                                        ],
                                        class: 'w-full',
                                      },
                                      null,
                                      8,
                                      ['modelValue', 'disabled', 'rules'],
                                    ),
                                  ]),
                                ]),
                                (0, n._)('div', $t, [
                                  (0, n._)('div', Jt, [
                                    (0, n.Wm)(
                                      Rn,
                                      {
                                        modelValue: (0, o.SU)(Nn).premium,
                                        'onUpdate:modelValue':
                                          s[23] ||
                                          (s[23] = function (e) {
                                            return ((0, o.SU)(Nn).premium = e);
                                          }),
                                        disabled: !(0, o.SU)(Nn).editMode,
                                        label: 'Premium',
                                        rules: [$l.isRequired, In.premium],
                                        class: 'w-full',
                                      },
                                      null,
                                      8,
                                      ['modelValue', 'disabled', 'rules'],
                                    ),
                                  ]),
                                  Xt,
                                ]),
                                (0, o.SU)(Nn).canEdit
                                  ? ((0, n.wg)(),
                                    (0, n.iD)('div', el, [
                                      (0, n.wy)(
                                        (0, n.Wm)(
                                          f,
                                          {
                                            color: '#007bff',
                                            size: 'sm',
                                            onClick: (0, a.iM)(jn, ['prevent']),
                                          },
                                          {
                                            default: (0, n.w5)(function () {
                                              return [(0, n.Uk)('Cancel')];
                                            }),
                                            _: 1,
                                          },
                                          8,
                                          ['onClick'],
                                        ),
                                        [[a.F8, (0, o.SU)(Nn).editMode]],
                                      ),
                                      (0, n.wy)(
                                        (0, n.Wm)(
                                          f,
                                          {
                                            color: '#26B99A',
                                            type: 'submit',
                                            size: 'sm',
                                          },
                                          {
                                            default: (0, n.w5)(function () {
                                              return [(0, n.Uk)('Update')];
                                            }),
                                            _: 1,
                                          },
                                          512,
                                        ),
                                        [[a.F8, (0, o.SU)(Nn).editMode]],
                                      ),
                                      (0, n.wy)(
                                        (0, n.Wm)(
                                          f,
                                          {
                                            color: '#007bff',
                                            size: 'sm',
                                            type: 'submit',
                                            onClick:
                                              s[24] ||
                                              (s[24] = (0, a.iM)(
                                                function (e) {
                                                  return ((0, o.SU)(
                                                    Nn,
                                                  ).editMode = !0);
                                                },
                                                ['prevent'],
                                              )),
                                          },
                                          {
                                            default: (0, n.w5)(function () {
                                              return [(0, n.Uk)('Edit')];
                                            }),
                                            _: 1,
                                          },
                                          512,
                                        ),
                                        [[a.F8, !(0, o.SU)(Nn).editMode]],
                                      ),
                                    ]))
                                  : (0, n.kq)('', !0),
                              ];
                            }),
                            _: 1,
                          },
                        ),
                      ]))
                    : (0, n.kq)('', !0),
                  (0, n._)('div', tl, [
                    (0, n._)('div', ll, [
                      (0, n._)('h3', nl, [
                        (0, n.Uk)(' Available Plans '),
                        (0, n.Wm)(
                          Zl,
                          { size: 'sm' },
                          {
                            default: (0, n.w5)(function () {
                              return [
                                (0, n.Uk)(
                                  (0, r.zw)(e.listQuotePlans.length || 0),
                                  1,
                                ),
                              ];
                            }),
                            _: 1,
                          },
                        ),
                      ]),
                      (0, n._)('div', ol, [
                        Hl.value.length > 0
                          ? ((0, n.wg)(),
                            (0, n.j4)(
                              f,
                              {
                                key: 0,
                                size: 'sm',
                                color: 'emerald',
                                onClick: (0, a.iM)(yn, ['prevent']),
                                loading: Bl.value,
                              },
                              {
                                default: (0, n.w5)(function () {
                                  return [(0, n.Uk)(' Download PDF ')];
                                }),
                                _: 1,
                              },
                              8,
                              ['onClick', 'loading'],
                            ))
                          : (0, n.kq)('', !0),
                        (0, n.Wm)(
                          f,
                          {
                            size: 'sm',
                            color: 'primary',
                            onClick:
                              s[25] ||
                              (s[25] = (0, a.iM)(
                                function (e) {
                                  return (v.createPlan = !0);
                                },
                                ['prevent'],
                              )),
                          },
                          {
                            default: (0, n.w5)(function () {
                              return [(0, n.Uk)(' Create Quote ')];
                            }),
                            _: 1,
                          },
                        ),
                        e.listQuotePlans.length > 0
                          ? ((0, n.wg)(),
                            (0, n.j4)(
                              f,
                              {
                                key: 1,
                                size: 'sm',
                                color: 'orange',
                                onClick:
                                  s[26] ||
                                  (s[26] = (0, a.iM)(
                                    function (t) {
                                      return Jl(
                                        e.ecomHealthInsuranceQuoteUrl +
                                          e.quote.uuid,
                                      );
                                    },
                                    ['prevent'],
                                  )),
                              },
                              {
                                default: (0, n.w5)(function () {
                                  return [(0, n.Uk)(' Copy Link ')];
                                }),
                                _: 1,
                              },
                            ))
                          : (0, n.kq)('', !0),
                      ]),
                    ]),
                    (0, n.Wm)(
                      Gl,
                      {
                        'items-selected': Hl.value,
                        'onUpdate:items-selected':
                          s[27] ||
                          (s[27] = function (e) {
                            return (Hl.value = e);
                          }),
                        'table-class-name': 'tablefixed compact',
                        headers: bn.columns,
                        items: e.listQuotePlans || [],
                        'border-cell': '',
                        'hide-rows-per-page': '',
                        'rows-per-page': 15,
                        'hide-footer': e.listQuotePlans.length < 15,
                      },
                      {
                        'item-actualPremium': (0, n.w5)(function (e) {
                          var t,
                            l = e.actualPremium;
                          return [
                            (0, n.Uk)(
                              (0, r.zw)(
                                ((t = l),
                                t == Math.floor(t) ? t : t.toFixed(2)),
                              ),
                              1,
                            ),
                          ];
                        }),
                        'item-action': (0, n.w5)(function (t) {
                          return [
                            (0, n._)('div', al, [
                              (0, n.Wm)(
                                f,
                                {
                                  size: 'xs',
                                  color: 'primary',
                                  outlined: '',
                                  onClick: (0, a.iM)(
                                    function (e) {
                                      return (
                                        (l = t),
                                        (jl.value = l),
                                        void (v.plan = !0)
                                      );
                                      var l;
                                    },
                                    ['prevent'],
                                  ),
                                },
                                {
                                  default: (0, n.w5)(function () {
                                    return [(0, n.Uk)(' View ')];
                                  }),
                                  _: 2,
                                },
                                1032,
                                ['onClick'],
                              ),
                              (0, n.Wm)(
                                f,
                                {
                                  size: 'xs',
                                  color: 'emerald',
                                  outlined: '',
                                  onClick: (0, a.iM)(
                                    function (l) {
                                      return Jl(
                                        e.ecomHealthInsuranceQuoteUrl +
                                          e.quote.uuid +
                                          '/payment/?providerCode='
                                            .concat(t.providerCode, '_')
                                            .concat(t.planCode, '&planId=')
                                            .concat(t.id),
                                      );
                                    },
                                    ['prevent'],
                                  ),
                                },
                                {
                                  default: (0, n.w5)(function () {
                                    return [(0, n.Uk)(' Copy ')];
                                  }),
                                  _: 2,
                                },
                                1032,
                                ['onClick'],
                              ),
                            ]),
                          ];
                        }),
                        _: 1,
                      },
                      8,
                      ['items-selected', 'headers', 'items', 'hide-footer'],
                    ),
                    (0, n.Wm)(
                      b,
                      {
                        modelValue: v.plan,
                        'onUpdate:modelValue':
                          s[28] ||
                          (s[28] = function (e) {
                            return (v.plan = e);
                          }),
                        size: 'xl',
                        'show-close': '',
                        backdrop: '',
                      },
                      {
                        header: (0, n.w5)(function () {
                          return [
                            (0, n.Uk)(
                              (0, r.zw)(jl.value.providerName) +
                                ' - ' +
                                (0, r.zw)(jl.value.name),
                              1,
                            ),
                          ];
                        }),
                        default: (0, n.w5)(function () {
                          return [
                            (0, n.Wm)(c.default, { plan: jl.value }, null, 8, [
                              'plan',
                            ]),
                          ];
                        }),
                        _: 1,
                      },
                      8,
                      ['modelValue'],
                    ),
                    (0, n.Wm)(
                      b,
                      {
                        modelValue: v.createPlan,
                        'onUpdate:modelValue':
                          s[29] ||
                          (s[29] = function (e) {
                            return (v.createPlan = e);
                          }),
                        size: 'lg',
                        'show-close': '',
                        backdrop: '',
                      },
                      {
                        header: (0, n.w5)(function () {
                          return [(0, n.Uk)(' Create Heath Quote ')];
                        }),
                        default: (0, n.w5)(function () {
                          return [
                            (0, n.Wm)(
                              m.default,
                              {
                                uuid: e.quote.uuid,
                                onSuccess: xn,
                                onError: Un,
                              },
                              null,
                              8,
                              ['uuid'],
                            ),
                          ];
                        }),
                        _: 1,
                      },
                      8,
                      ['modelValue'],
                    ),
                  ]),
                  (0, n._)('div', rl, [
                    (0, n._)('div', il, [
                      (0, n._)('h3', ul, [
                        (0, n.Uk)(' Documents '),
                        (0, n.Wm)(
                          Zl,
                          { size: 'sm' },
                          {
                            default: (0, n.w5)(function () {
                              return [
                                (0, n.Uk)(
                                  (0, r.zw)(e.quoteDocuments.length || 0),
                                  1,
                                ),
                              ];
                            }),
                            _: 1,
                          },
                        ),
                      ]),
                      (0, n.Wm)(
                        f,
                        {
                          onClick:
                            s[30] ||
                            (s[30] = (0, a.iM)(
                              function (e) {
                                return (v.doc = !0);
                              },
                              ['prevent'],
                            )),
                          size: 'sm',
                          color: 'orange',
                        },
                        {
                          default: (0, n.w5)(function () {
                            return [(0, n.Uk)(' Upload Documents ')];
                          }),
                          _: 1,
                        },
                      ),
                    ]),
                    (0, n.Wm)(
                      Gl,
                      {
                        'table-class-name': 'compact',
                        headers: Sn.columns,
                        items: e.quoteDocuments || [],
                        'border-cell': '',
                        'hide-rows-per-page': '',
                        'rows-per-page': 15,
                        'hide-footer': e.quoteDocuments.length < 15,
                      },
                      {
                        'item-original_name': (0, n.w5)(function (t) {
                          return [
                            (0, n._)(
                              'a',
                              {
                                href: e.cdnPath + t.doc_url,
                                target: '_blank',
                                class: 'text-primary-600',
                              },
                              (0, r.zw)(t.original_name),
                              9,
                              sl,
                            ),
                          ];
                        }),
                        'item-action': (0, n.w5)(function (e) {
                          var t = e.doc_name;
                          return [
                            (0, n._)('div', null, [
                              (0, n.Wm)(
                                f,
                                {
                                  size: 'xs',
                                  color: 'error',
                                  outlined: '',
                                  onClick: (0, a.iM)(
                                    function (e) {
                                      return (
                                        (l = t),
                                        (v.docConfirm = !0),
                                        void (Pl.docs = l)
                                      );
                                      var l;
                                    },
                                    ['prevent'],
                                  ),
                                },
                                {
                                  default: (0, n.w5)(function () {
                                    return [(0, n.Uk)(' Delete ')];
                                  }),
                                  _: 2,
                                },
                                1032,
                                ['onClick'],
                              ),
                            ]),
                          ];
                        }),
                        _: 1,
                      },
                      8,
                      ['headers', 'items', 'hide-footer'],
                    ),
                    (0, n.Wm)(
                      b,
                      {
                        modelValue: v.doc,
                        'onUpdate:modelValue':
                          s[31] ||
                          (s[31] = function (e) {
                            return (v.doc = e);
                          }),
                        size: 'xl',
                        'show-close': '',
                        backdrop: '',
                      },
                      {
                        header: (0, n.w5)(function () {
                          return [(0, n.Uk)(' Upload Documents ')];
                        }),
                        default: (0, n.w5)(function () {
                          return [
                            (0, n.Wm)(
                              d.default,
                              {
                                members:
                                  ((t = e.membersDetail),
                                  t
                                    .map(function (e) {
                                      return {
                                        id: e.id,
                                        name: en(e.member_category_id).value,
                                      };
                                    })
                                    .filter(function (e) {
                                      return void 0 !== e.name;
                                    })),
                                'doc-types': e.documentTypes,
                                docs: e.quoteDocuments || [],
                                cdn: e.cdnPath,
                              },
                              null,
                              8,
                              ['members', 'doc-types', 'docs', 'cdn'],
                            ),
                          ];
                          var t;
                        }),
                        _: 1,
                      },
                      8,
                      ['modelValue'],
                    ),
                    (0, n.Wm)(
                      b,
                      {
                        modelValue: v.docConfirm,
                        'onUpdate:modelValue':
                          s[33] ||
                          (s[33] = function (e) {
                            return (v.docConfirm = e);
                          }),
                        'show-close': '',
                        backdrop: '',
                      },
                      {
                        header: (0, n.w5)(function () {
                          return [(0, n.Uk)(' Delete Document ')];
                        }),
                        actions: (0, n.w5)(function () {
                          return [
                            (0, n._)('div', cl, [
                              (0, n.Wm)(
                                f,
                                {
                                  size: 'sm',
                                  ghost: '',
                                  onClick:
                                    s[32] ||
                                    (s[32] = (0, a.iM)(
                                      function (e) {
                                        return (v.docConfirm = !1);
                                      },
                                      ['prevent'],
                                    )),
                                },
                                {
                                  default: (0, n.w5)(function () {
                                    return [(0, n.Uk)(' Cancel ')];
                                  }),
                                  _: 1,
                                },
                              ),
                              (0, n.Wm)(
                                f,
                                {
                                  size: 'sm',
                                  color: 'error',
                                  onClick: (0, a.iM)(Vn, ['prevent']),
                                  loading: Sn.isLoading,
                                },
                                {
                                  default: (0, n.w5)(function () {
                                    return [(0, n.Uk)(' Delete ')];
                                  }),
                                  _: 1,
                                },
                                8,
                                ['onClick', 'loading'],
                              ),
                            ]),
                          ];
                        }),
                        default: (0, n.w5)(function () {
                          return [dl];
                        }),
                        _: 1,
                      },
                      8,
                      ['modelValue'],
                    ),
                  ]),
                  (0, n._)('div', ml, [
                    (0, n._)('div', pl, [
                      (0, n._)('h3', fl, [
                        (0, n.Uk)(' Lead Activities '),
                        (0, n.Wm)(
                          Zl,
                          { size: 'sm' },
                          {
                            default: (0, n.w5)(function () {
                              return [
                                (0, n.Uk)(
                                  (0, r.zw)(e.activities.length || 0),
                                  1,
                                ),
                              ];
                            }),
                            _: 1,
                          },
                        ),
                      ]),
                      (0, n.Wm)(
                        f,
                        {
                          size: 'sm',
                          color: 'orange',
                          onClick: (0, a.iM)(Cn, ['prevent']),
                        },
                        {
                          default: (0, n.w5)(function () {
                            return [(0, n.Uk)(' Add Activity ')];
                          }),
                          _: 1,
                        },
                        8,
                        ['onClick'],
                      ),
                    ]),
                    (0, n.Wm)(Kl, { class: 'my-4' }),
                    (0, n.Wm)(
                      Gl,
                      {
                        'table-class-name': 'compact',
                        headers: kn,
                        items: e.activities,
                        'border-cell': '',
                        'hide-rows-per-page': '',
                        'rows-per-page': 15,
                        'hide-footer': e.activities.length < 15,
                      },
                      {
                        'item-status': (0, n.w5)(function (e) {
                          var t = e.status,
                            o = e.id;
                          return [
                            (0, n.Wm)(
                              Fn,
                              {
                                color: 'emerald',
                                size: 'xl',
                                modelValue: 1 === t,
                                disabled: 1 === t,
                                onChange: function (e) {
                                  return (function (e) {
                                    (qn.activity_id = e),
                                      qn.post('/activities/updateStatus', {
                                        preserveScroll: !0,
                                        onSuccess: function () {
                                          l.success({
                                            title: 'Lead Activity Done',
                                            position: 'top',
                                          });
                                        },
                                      });
                                  })(o);
                                },
                              },
                              null,
                              8,
                              ['modelValue', 'disabled', 'onChange'],
                            ),
                          ];
                        }),
                        'item-action': (0, n.w5)(function (e) {
                          return [
                            (0, n._)('div', _l, [
                              (0, n.Wm)(
                                f,
                                {
                                  size: 'xs',
                                  color: 'primary',
                                  outlined: '',
                                  disabled: 1 === e.status,
                                  onClick: (0, a.iM)(
                                    function (t) {
                                      return (
                                        (l = e),
                                        (Il.value = !0),
                                        (v.activity = !0),
                                        (qn.activity_id = l.id),
                                        (qn.uuid = l.uuid),
                                        (qn.title = l.title),
                                        (qn.description = l.description),
                                        (qn.due_date = l.due_date
                                          ? l.due_date
                                              .split(' ')[0]
                                              .split('-')
                                              .reverse()
                                              .join('-') +
                                            'T' +
                                            l.due_date.split(' ')[1]
                                          : null),
                                        (qn.assignee_id = l.assignee_id),
                                        void (qn.status = l.status)
                                      );
                                      var l;
                                    },
                                    ['prevent'],
                                  ),
                                },
                                {
                                  default: (0, n.w5)(function () {
                                    return [(0, n.Uk)(' Edit ')];
                                  }),
                                  _: 2,
                                },
                                1032,
                                ['disabled', 'onClick'],
                              ),
                              (0, n.Wm)(
                                f,
                                {
                                  size: 'xs',
                                  color: 'error',
                                  disabled: 1 === e.status,
                                  outlined: '',
                                  onClick: (0, a.iM)(
                                    function (t) {
                                      return (
                                        (l = e.id),
                                        (v.activityConfirm = !0),
                                        void (Pl.activity = l)
                                      );
                                      var l;
                                    },
                                    ['prevent'],
                                  ),
                                },
                                {
                                  default: (0, n.w5)(function () {
                                    return [(0, n.Uk)(' Delete ')];
                                  }),
                                  _: 2,
                                },
                                1032,
                                ['disabled', 'onClick'],
                              ),
                            ]),
                          ];
                        }),
                        _: 1,
                      },
                      8,
                      ['items', 'hide-footer'],
                    ),
                    (0, n.Wm)(
                      b,
                      {
                        modelValue: v.activity,
                        'onUpdate:modelValue':
                          s[39] ||
                          (s[39] = function (e) {
                            return (v.activity = e);
                          }),
                        size: 'lg',
                        'show-close': '',
                        backdrop: '',
                      },
                      {
                        header: (0, n.w5)(function () {
                          return [
                            (0, n.Uk)(
                              (0, r.zw)(Il.value ? 'Edit' : 'Add') +
                                ' Lead Activity ',
                              1,
                            ),
                          ];
                        }),
                        default: (0, n.w5)(function () {
                          return [
                            (0, n.Wm)(
                              w,
                              { onSubmit: Wn, 'auto-focus': !1 },
                              {
                                default: (0, n.w5)(function () {
                                  return [
                                    (0, n._)('div', vl, [
                                      (0, n.Wm)(
                                        Rn,
                                        {
                                          modelValue: (0, o.SU)(qn).title,
                                          'onUpdate:modelValue':
                                            s[34] ||
                                            (s[34] = function (e) {
                                              return ((0, o.SU)(qn).title = e);
                                            }),
                                          label: 'Title',
                                          rules: [$l.isRequired],
                                          class: 'w-full',
                                        },
                                        null,
                                        8,
                                        ['modelValue', 'rules'],
                                      ),
                                      (0, n.Wm)(
                                        Bn,
                                        {
                                          modelValue: (0, o.SU)(qn).description,
                                          'onUpdate:modelValue':
                                            s[35] ||
                                            (s[35] = function (e) {
                                              return ((0, o.SU)(
                                                qn,
                                              ).description = e);
                                            }),
                                          label: 'Description',
                                          'adjust-to-text': !1,
                                          class: 'w-full',
                                        },
                                        null,
                                        8,
                                        ['modelValue'],
                                      ),
                                      (0, n.Wm)(
                                        g,
                                        {
                                          modelValue: (0, o.SU)(qn).assignee_id,
                                          'onUpdate:modelValue':
                                            s[36] ||
                                            (s[36] = function (e) {
                                              return ((0, o.SU)(
                                                qn,
                                              ).assignee_id = e);
                                            }),
                                          label: 'Assignee',
                                          options: (0, o.SU)(ln),
                                          rules: [$l.isRequired],
                                          placeholder: 'Select Assignee',
                                          class: 'w-full',
                                        },
                                        null,
                                        8,
                                        ['modelValue', 'options', 'rules'],
                                      ),
                                      (0, n.Wm)(
                                        Rn,
                                        {
                                          modelValue: (0, o.SU)(qn).due_date,
                                          'onUpdate:modelValue':
                                            s[37] ||
                                            (s[37] = function (e) {
                                              return ((0, o.SU)(qn).due_date =
                                                e);
                                            }),
                                          label: 'Due Date',
                                          type: 'datetime-local',
                                          rules: [$l.isRequired],
                                          class: 'w-full',
                                        },
                                        null,
                                        8,
                                        ['modelValue', 'rules'],
                                      ),
                                    ]),
                                    (0, n._)('div', gl, [
                                      (0, n.Wm)(
                                        f,
                                        {
                                          size: 'sm',
                                          onClick:
                                            s[38] ||
                                            (s[38] = (0, a.iM)(
                                              function (e) {
                                                return (v.activity = !1);
                                              },
                                              ['prevent'],
                                            )),
                                        },
                                        {
                                          default: (0, n.w5)(function () {
                                            return [(0, n.Uk)(' Cancel ')];
                                          }),
                                          _: 1,
                                        },
                                      ),
                                      (0, n.Wm)(
                                        f,
                                        {
                                          size: 'sm',
                                          color: 'emerald',
                                          loading: (0, o.SU)(qn).processing,
                                          type: 'submit',
                                        },
                                        {
                                          default: (0, n.w5)(function () {
                                            return [
                                              (0, n.Uk)(
                                                (0, r.zw)(
                                                  Il.value ? 'Update' : 'Save',
                                                ),
                                                1,
                                              ),
                                            ];
                                          }),
                                          _: 1,
                                        },
                                        8,
                                        ['loading'],
                                      ),
                                    ]),
                                  ];
                                }),
                                _: 1,
                              },
                            ),
                          ];
                        }),
                        _: 1,
                      },
                      8,
                      ['modelValue'],
                    ),
                    (0, n.Wm)(
                      b,
                      {
                        modelValue: v.activityConfirm,
                        'onUpdate:modelValue':
                          s[41] ||
                          (s[41] = function (e) {
                            return (v.activityConfirm = e);
                          }),
                        'show-close': '',
                        backdrop: '',
                      },
                      {
                        header: (0, n.w5)(function () {
                          return [(0, n.Uk)(' Delete Activity ')];
                        }),
                        actions: (0, n.w5)(function () {
                          return [
                            (0, n._)('div', hl, [
                              (0, n.Wm)(
                                f,
                                {
                                  size: 'sm',
                                  ghost: '',
                                  onClick:
                                    s[40] ||
                                    (s[40] = (0, a.iM)(
                                      function (e) {
                                        return (v.activityConfirm = !1);
                                      },
                                      ['prevent'],
                                    )),
                                },
                                {
                                  default: (0, n.w5)(function () {
                                    return [(0, n.Uk)(' Cancel ')];
                                  }),
                                  _: 1,
                                },
                              ),
                              (0, n.Wm)(
                                f,
                                {
                                  size: 'sm',
                                  color: 'error',
                                  loading: (0, o.SU)(qn).processing,
                                  onClick: (0, a.iM)(zn, ['prevent']),
                                },
                                {
                                  default: (0, n.w5)(function () {
                                    return [(0, n.Uk)(' Delete ')];
                                  }),
                                  _: 1,
                                },
                                8,
                                ['loading', 'onClick'],
                              ),
                            ]),
                          ];
                        }),
                        default: (0, n.w5)(function () {
                          return [wl];
                        }),
                        _: 1,
                      },
                      8,
                      ['modelValue'],
                    ),
                  ]),
                  (0, n._)('div', bl, [
                    (0, n._)('div', yl, [
                      (0, n._)('h3', xl, [
                        (0, n.Uk)(' Customer Additional Contacts '),
                        (0, n.Wm)(
                          Zl,
                          { size: 'sm' },
                          {
                            default: (0, n.w5)(function () {
                              return [
                                (0, n.Uk)(
                                  (0, r.zw)(
                                    e.customerAdditionalContacts.length || 0,
                                  ),
                                  1,
                                ),
                              ];
                            }),
                            _: 1,
                          },
                        ),
                      ]),
                      (0, n.Wm)(
                        f,
                        {
                          size: 'sm',
                          color: 'orange',
                          onClick:
                            s[42] ||
                            (s[42] = (0, a.iM)(
                              function (e) {
                                return (v.addContact = !0);
                              },
                              ['prevent'],
                            )),
                        },
                        {
                          default: (0, n.w5)(function () {
                            return [(0, n.Uk)(' Add Additional Contacts ')];
                          }),
                          _: 1,
                        },
                      ),
                    ]),
                    (0, n.Wm)(
                      Gl,
                      {
                        'table-class-name': 'compact',
                        headers: En,
                        items: e.customerAdditionalContacts || [],
                        'border-cell': '',
                        'hide-rows-per-page': '',
                        'hide-footer': '',
                      },
                      {
                        'item-key': (0, n.w5)(function (e) {
                          return [
                            'email' === e.key
                              ? ((0, n.wg)(),
                                (0, n.iD)('span', Ul, ' Email Address '))
                              : ((0, n.wg)(),
                                (0, n.iD)('span', Sl, ' Mobile Number ')),
                          ];
                        }),
                        'item-action': (0, n.w5)(function (e) {
                          return [
                            (0, n._)('div', Vl, [
                              (0, n.Wm)(
                                f,
                                {
                                  size: 'xs',
                                  color: 'emerald',
                                  outlined: '',
                                  onClick: (0, a.iM)(
                                    function (t) {
                                      return (
                                        (l = e),
                                        (v.contactPrimaryConfirm = !0),
                                        void (Ll.contactPrimary = l)
                                      );
                                      var l;
                                    },
                                    ['prevent'],
                                  ),
                                },
                                {
                                  default: (0, n.w5)(function () {
                                    return [(0, n.Uk)(' Make Primary ')];
                                  }),
                                  _: 2,
                                },
                                1032,
                                ['onClick'],
                              ),
                              (0, n.Wm)(
                                f,
                                {
                                  size: 'xs',
                                  color: 'error',
                                  outlined: '',
                                  onClick: (0, a.iM)(
                                    function (t) {
                                      return (
                                        (l = e.id),
                                        (v.contactDeleteConfirm = !0),
                                        void (Pl.contact = l)
                                      );
                                      var l;
                                    },
                                    ['prevent'],
                                  ),
                                },
                                {
                                  default: (0, n.w5)(function () {
                                    return [(0, n.Uk)(' Delete ')];
                                  }),
                                  _: 2,
                                },
                                1032,
                                ['onClick'],
                              ),
                            ]),
                          ];
                        }),
                        _: 1,
                      },
                      8,
                      ['items'],
                    ),
                    (0, n.Wm)(
                      b,
                      {
                        modelValue: v.addContact,
                        'onUpdate:modelValue':
                          s[46] ||
                          (s[46] = function (e) {
                            return (v.addContact = e);
                          }),
                        size: 'lg',
                        'show-close': '',
                        backdrop: '',
                      },
                      {
                        header: (0, n.w5)(function () {
                          return [(0, n.Uk)(' Add Additional Contacts ')];
                        }),
                        default: (0, n.w5)(function () {
                          return [
                            (0, n.Wm)(
                              w,
                              { onSubmit: An, 'auto-focus': !1 },
                              {
                                default: (0, n.w5)(function () {
                                  return [
                                    (0, n._)('div', kl, [
                                      (0, n.Wm)(
                                        g,
                                        {
                                          modelValue: (0, o.SU)(Dn)
                                            .additional_contact_type,
                                          'onUpdate:modelValue':
                                            s[43] ||
                                            (s[43] = function (e) {
                                              return ((0, o.SU)(
                                                Dn,
                                              ).additional_contact_type = e);
                                            }),
                                          label: 'Type',
                                          options: [
                                            { value: 'email', label: 'Email' },
                                            {
                                              value: 'mobile_no',
                                              label: 'Mobile Number',
                                            },
                                          ],
                                          rules: [$l.isRequired],
                                          placeholder: 'Select Type',
                                          class: 'w-full',
                                        },
                                        null,
                                        8,
                                        ['modelValue', 'rules'],
                                      ),
                                      (0, n.Wm)(
                                        Rn,
                                        {
                                          modelValue: (0, o.SU)(Dn)
                                            .additional_contact_val,
                                          'onUpdate:modelValue':
                                            s[44] ||
                                            (s[44] = function (e) {
                                              return ((0, o.SU)(
                                                Dn,
                                              ).additional_contact_val = e);
                                            }),
                                          label: 'Value',
                                          rules: [$l.isRequired],
                                          class: 'w-full',
                                        },
                                        null,
                                        8,
                                        ['modelValue', 'rules'],
                                      ),
                                    ]),
                                    (0, n._)('div', ql, [
                                      (0, n.Wm)(
                                        f,
                                        {
                                          size: 'sm',
                                          onClick:
                                            s[45] ||
                                            (s[45] = (0, a.iM)(
                                              function (e) {
                                                return (v.addContact = !1);
                                              },
                                              ['prevent'],
                                            )),
                                        },
                                        {
                                          default: (0, n.w5)(function () {
                                            return [(0, n.Uk)(' Cancel ')];
                                          }),
                                          _: 1,
                                        },
                                      ),
                                      (0, n.Wm)(
                                        f,
                                        {
                                          size: 'sm',
                                          color: 'emerald',
                                          loading: (0, o.SU)(Dn).processing,
                                          type: 'submit',
                                        },
                                        {
                                          default: (0, n.w5)(function () {
                                            return [(0, n.Uk)(' Save ')];
                                          }),
                                          _: 1,
                                        },
                                        8,
                                        ['loading'],
                                      ),
                                    ]),
                                  ];
                                }),
                                _: 1,
                              },
                            ),
                          ];
                        }),
                        _: 1,
                      },
                      8,
                      ['modelValue'],
                    ),
                    (0, n.Wm)(
                      b,
                      {
                        modelValue: v.contactDeleteConfirm,
                        'onUpdate:modelValue':
                          s[48] ||
                          (s[48] = function (e) {
                            return (v.contactDeleteConfirm = e);
                          }),
                        'show-close': '',
                        backdrop: '',
                      },
                      {
                        header: (0, n.w5)(function () {
                          return [(0, n.Uk)(' Delete Additional Contact ')];
                        }),
                        actions: (0, n.w5)(function () {
                          return [
                            (0, n._)('div', Wl, [
                              (0, n.Wm)(
                                f,
                                {
                                  size: 'sm',
                                  ghost: '',
                                  onClick:
                                    s[47] ||
                                    (s[47] = (0, a.iM)(
                                      function (e) {
                                        return (v.contactDeleteConfirm = !1);
                                      },
                                      ['prevent'],
                                    )),
                                },
                                {
                                  default: (0, n.w5)(function () {
                                    return [(0, n.Uk)(' Cancel ')];
                                  }),
                                  _: 1,
                                },
                              ),
                              (0, n.Wm)(
                                f,
                                {
                                  size: 'sm',
                                  color: 'error',
                                  onClick: (0, a.iM)(Mn, ['prevent']),
                                  loading: Fl.value,
                                },
                                {
                                  default: (0, n.w5)(function () {
                                    return [(0, n.Uk)(' Delete ')];
                                  }),
                                  _: 1,
                                },
                                8,
                                ['onClick', 'loading'],
                              ),
                            ]),
                          ];
                        }),
                        default: (0, n.w5)(function () {
                          return [Cl];
                        }),
                        _: 1,
                      },
                      8,
                      ['modelValue'],
                    ),
                    (0, n.Wm)(
                      b,
                      {
                        modelValue: v.contactPrimaryConfirm,
                        'onUpdate:modelValue':
                          s[50] ||
                          (s[50] = function (e) {
                            return (v.contactPrimaryConfirm = e);
                          }),
                        'show-close': '',
                        backdrop: '',
                      },
                      {
                        header: (0, n.w5)(function () {
                          return [(0, n.Uk)(' Primary Additional Contact ')];
                        }),
                        actions: (0, n.w5)(function () {
                          return [
                            (0, n._)('div', El, [
                              (0, n.Wm)(
                                f,
                                {
                                  size: 'sm',
                                  ghost: '',
                                  onClick:
                                    s[49] ||
                                    (s[49] = (0, a.iM)(
                                      function (e) {
                                        return (v.contactPrimaryConfirm = !1);
                                      },
                                      ['prevent'],
                                    )),
                                },
                                {
                                  default: (0, n.w5)(function () {
                                    return [(0, n.Uk)(' Cancel ')];
                                  }),
                                  _: 1,
                                },
                              ),
                              (0, n.Wm)(
                                f,
                                {
                                  size: 'sm',
                                  color: 'emerald',
                                  onClick: (0, a.iM)(On, ['prevent']),
                                  loading: Fl.value,
                                },
                                {
                                  default: (0, n.w5)(function () {
                                    return [(0, n.Uk)(' Confirm ')];
                                  }),
                                  _: 1,
                                },
                                8,
                                ['onClick', 'loading'],
                              ),
                            ]),
                          ];
                        }),
                        default: (0, n.w5)(function () {
                          return [zl];
                        }),
                        _: 1,
                      },
                      8,
                      ['modelValue'],
                    ),
                  ]),
                  (0, n._)('div', Dl, [
                    (0, n._)('div', null, [
                      Al,
                      (0, n.Wm)(Kl, { class: 'mb-4 mt-1' }),
                    ]),
                    null === Pn.value
                      ? ((0, n.wg)(),
                        (0, n.iD)('div', Ml, [
                          (0, n.Wm)(
                            f,
                            {
                              size: 'sm',
                              color: 'primary',
                              outlined: '',
                              onClick: (0, a.iM)(Ln, ['prevent']),
                              loading: Yl.value,
                            },
                            {
                              default: (0, n.w5)(function () {
                                return [(0, n.Uk)(' Load History Data ')];
                              }),
                              _: 1,
                            },
                            8,
                            ['onClick', 'loading'],
                          ),
                        ]))
                      : ((0, n.wg)(),
                        (0, n.j4)(
                          Gl,
                          {
                            key: 1,
                            'table-class-name': 'compact',
                            headers: Tn,
                            items: Pn.value || [],
                            'border-cell': '',
                            'hide-rows-per-page': '',
                            'rows-per-page': 15,
                            'hide-footer': Pn.value.length < 15,
                          },
                          null,
                          8,
                          ['items', 'hide-footer'],
                        )),
                  ]),
                ])
              );
            }
          );
        },
      };
    },
    4053: (e, t, l) => {
      var n = {
        './HealthQuote/Cards': 3356,
        './HealthQuote/Cards.vue': 3356,
        './HealthQuote/Create': 1074,
        './HealthQuote/Create.vue': 1074,
        './HealthQuote/Edit': 8477,
        './HealthQuote/Edit.vue': 8477,
        './HealthQuote/Index': 8450,
        './HealthQuote/Index.vue': 8450,
        './HealthQuote/Partials/AvailablePlans': 8876,
        './HealthQuote/Partials/AvailablePlans.vue': 8876,
        './HealthQuote/Partials/CreatePlan': 7826,
        './HealthQuote/Partials/CreatePlan.vue': 7826,
        './HealthQuote/Partials/DocumentUploader': 1314,
        './HealthQuote/Partials/DocumentUploader.vue': 1314,
        './HealthQuote/Show': 4377,
        './HealthQuote/Show.vue': 4377,
      };
      function o(e) {
        var t = a(e);
        return l(t);
      }
      function a(e) {
        if (!l.o(n, e)) {
          var t = new Error("Cannot find module '" + e + "'");
          throw ((t.code = 'MODULE_NOT_FOUND'), t);
        }
        return n[e];
      }
      (o.keys = function () {
        return Object.keys(n);
      }),
        (o.resolve = a),
        (e.exports = o),
        (o.id = 4053);
    },
    4654: () => {},
    67: () => {},
  },
  e => {
    var t = t => e((e.s = t));
    e.O(0, [170, 786, 898], () => (t(2163), t(2584), t(2688)));
    e.O();
  },
]);
