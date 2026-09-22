#!/bin/bash
# 外から転送されてコンテナへ入る通信を、公開したいポートだけに絞る。
#
# docker の公開ポートは nat/PREROUTING で DNAT され、そのまま FORWARD へ回る。
# ホストの INPUT に書いた「22/80/443 以外は REJECT」はこの経路に当たらないため、
# compose に ports: を 1 行足すと、ホストのファイアウォールと無関係に外へ出る。
# DOCKER-USER は docker が FORWARD の先頭で必ず通す空のチェーンで、ここが
# その差を埋める唯一の場所になる。
#
# 1 本目が無いと、コンテナから外へ出した通信の戻りが落ちる。
# 2 本目で許すのは DNAT 後の宛先ポート、つまりコンテナ側の番号であって、
# ホスト側で公開した番号ではない。コンテナの 80 番を別のホストポートで
# 公開すると、この規則では通ってしまう。
set -eu

NIC="${1:-enp0s6}"

iptables -F DOCKER-USER
iptables -A DOCKER-USER -i "$NIC" -m conntrack --ctstate RELATED,ESTABLISHED -j RETURN
iptables -A DOCKER-USER -i "$NIC" -p tcp -m multiport --dports 80,443 -j RETURN
iptables -A DOCKER-USER -i "$NIC" -j DROP
